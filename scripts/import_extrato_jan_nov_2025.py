import re
from collections import defaultdict
from datetime import date

import fitz
import requests
from rapidocr_onnxruntime import RapidOCR


BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"
PDF_PATH = "Initial Load/Extrato - Jan a Nov 2025.pdf"


def parse_amount(value):
    text = value.replace(".", "").replace(",", ".")
    return round(float(text), 2)


def ymd(day_month, year=2025):
    day, month = [int(part) for part in day_month.split("/")]
    return date(year, month, day).strftime("%Y-%m-%d")


def first_business_day(year, month):
    d = date(year, month, 1)
    while d.weekday() >= 5:
        d = date.fromordinal(d.toordinal() + 1)
    return d.strftime("%Y-%m-%d")


def normalize_text(text):
    return re.sub(r"\s+", " ", text or "").strip()


def month_from_date(date_token):
    try:
        return int(date_token.split("/")[1])
    except Exception:
        return 0


def extract_candidate_outflows_from_ocr(pdf_path):
    ocr = RapidOCR()
    doc = fitz.open(pdf_path)
    lines = []

    for page in doc:
        pix = page.get_pixmap(matrix=fitz.Matrix(2, 2), alpha=False)
        result, _ = ocr(pix.tobytes("png"))
        if not result:
            continue
        for row in result:
            text = normalize_text(row[1])
            if text:
                lines.append(text)

    keywords = [
        "RECEITA FEDERAL",
        "SIMPLES NACIONAL",
        "NOTRE DAME",
        "INTERMEDICA",
        "AMIL",
        "VINDI",
        "EVOLUCAO",
        "GUSTAVO DA SILVA",
        " BOLETO",
    ]

    candidates = []
    for idx, line in enumerate(lines):
        up = line.upper()
        if not any(key in up for key in keywords):
            continue

        window = " ".join(lines[max(0, idx - 2): min(len(lines), idx + 3)])
        amount_match = re.search(r"-?R\$\s*([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2})", window, re.I)
        if not amount_match:
            amount_match = re.search(r"([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2})", window)
            if not amount_match:
                continue

        amount = parse_amount(amount_match.group(1))
        if amount <= 0:
            continue

        dates = re.findall(r"\b\d{2}/\d{2}\b", window)
        if not dates:
            continue
        accounting_date = dates[-1]
        month = month_from_date(accounting_date)
        if month < 1 or month > 11:
            continue

        candidates.append(
            {
                "line": window,
                "description": line,
                "amount": amount,
                "date": accounting_date,
                "month": month,
            }
        )

    # Deduplicate exact OCR duplicates.
    unique = {}
    for row in candidates:
        key = (row["date"], row["description"], f"{row['amount']:.2f}")
        unique[key] = row

    return list(unique.values())


def api_get(session, path):
    response = session.get(f"{BASE_URL}{path}")
    response.raise_for_status()
    return response.json().get("data", [])


def api_post(session, path, payload):
    response = session.post(f"{BASE_URL}{path}", json=payload)
    response.raise_for_status()
    return response.json().get("data", {})


def ensure_service_code(session, name, description):
    current = api_get(session, "/api/service-codes")
    for row in current:
        if row.get("Name") == name:
            return row
    rec = api_post(
        session,
        "/api/service-codes",
        {
            "Name": name,
            "Description": description,
            "DefaultPrice": 0,
            "IsActive": "1",
            "IsBlocked": "0",
        },
    )
    all_rows = api_get(session, "/api/service-codes")
    return next(item for item in all_rows if int(item["RecId"]) == int(rec["RecId"]))


def ensure_vendor(session, name):
    current = api_get(session, "/api/vendors")
    for row in current:
        if row.get("Name", "").strip().upper() == name.strip().upper():
            return row
    alias = re.sub(r"[^A-Za-z0-9]", "", name.upper())[:20] or "VENDOR"
    rec = api_post(
        session,
        "/api/vendors",
        {
            "Party": {
                "PartyType": "O",
                "Name": name,
                "Alias": alias,
                "TaxId": "",
                "IsActive": "1",
                "IsBlocked": "0",
                "Addresses": [
                    {
                        "AddressType": "B",
                        "ZipCode": "",
                        "Street": "",
                        "StreetNumber": "",
                        "Complement": "",
                        "District": "",
                        "City": "",
                        "State": "",
                        "Country": "BRASIL",
                        "IsPrimary": "1",
                    }
                ],
                "Contacts": [],
            },
            "CompanyType": "JURIDICA",
            "CurrencyCode": "BRL",
            "PaymentTermDays": 0,
            "VendorGroup": "DEFAULT",
            "IsActive": "1",
            "IsBlocked": "0",
            "ContactPersons": [],
        },
    )
    all_rows = api_get(session, "/api/vendors")
    return next(item for item in all_rows if int(item["RecId"]) == int(rec["RecId"]))


def ensure_bank(session):
    current = api_get(session, "/api/bank-accounts")
    for row in current:
        if row.get("BankName", "").strip().upper() == "C6 BANK":
            return row
    rec = api_post(
        session,
        "/api/bank-accounts",
        {
            "BankName": "C6 Bank",
            "AccountNumber": "4701708",
            "AccountDigit": "2",
            "Description": "Conta corrente principal C6",
            "IsActive": "1",
            "IsBlocked": "0",
        },
    )
    rows = api_get(session, "/api/bank-accounts")
    return next(item for item in rows if int(item["RecId"]) == int(rec["RecId"]))


def ensure_tax_type(session, name):
    rows = api_get(session, "/api/tax-types")
    for row in rows:
        if row.get("Name", "").strip().upper() == name.strip().upper():
            return row
    rec = api_post(
        session,
        "/api/tax-types",
        {
            "Name": name,
            "Description": f"{name} - Extrato OCR",
            "IsActive": "1",
            "IsBlocked": "0",
        },
    )
    rows = api_get(session, "/api/tax-types")
    return next(item for item in rows if int(item["RecId"]) == int(rec["RecId"]))


def ensure_service_purchase(session, vend_account, purch_number, issue_date, due_date, service_code_id, description, amount):
    current = api_get(session, "/api/purchase-orders/services")
    for row in current:
        if str(row.get("PurchNumber", "")).strip() == purch_number:
            return
    api_post(
        session,
        "/api/purchase-orders/services",
        {
            "VendAccount": vend_account,
            "PurchNumber": purch_number,
            "PurchDate": issue_date,
            "DueDate": due_date,
            "Status": "P",
            "DeductionAmount": 0,
            "Notes": "Extrato Jan-Nov/2025 OCR",
            "IsActive": "1",
            "Lines": [
                {
                    "ServiceCodeId": int(service_code_id),
                    "Description": description,
                    "Quantity": 1,
                    "UnitPrice": amount,
                    "LineAmount": amount,
                }
            ],
        },
    )


def ensure_journal(session, endpoint, description, journal_date, lines):
    current = api_get(session, endpoint)
    for row in current:
        if str(row.get("Description", "")).strip() == description:
            return
    api_post(
        session,
        endpoint,
        {
            "Description": description,
            "JournalDate": journal_date,
            "Posted": "1",
            "IsActive": "1",
            "Lines": lines,
        },
    )


def main():
    rows = extract_candidate_outflows_from_ocr(PDF_PATH)

    session = requests.Session()
    token = session.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    session.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    vendor_locaweb = ensure_vendor(session, "LOCAWEB SERVICOS DE INTERNET S.A.")
    vendor_amil = ensure_vendor(session, "AMIL ASSISTENCIA MEDICA INTERNACIONAL S.A.")
    vendor_notre = ensure_vendor(session, "NOTRE DAME INTERMEDICA SAUDE S.A.")
    vendor_evolucao = ensure_vendor(session, "EVOLUCAO GESTAO EMPRESARIAL LTDA")
    bank = ensure_bank(session)

    code_health = ensure_service_code(session, "Despesa - Plano de Saude", "Tomada de servico de plano de saude")
    code_gateway = ensure_service_code(session, "Despesa - Gateway de Pagamento", "Tomada de servico para pagamento online")
    code_accounting = ensure_service_code(session, "Despesa - Contabilidade", "Tomada de servico de contabilidade")

    tax_inss = ensure_tax_type(session, "INSS")
    tax_simple = ensure_tax_type(session, "Simples Nacional")

    taxes = []
    service_rows = []
    gustavo_by_month = defaultdict(float)

    for row in rows:
        up = row["description"].upper()
        month = row["month"]
        dt = ymd(row["date"])

        if "GUSTAVO" in up:
            gustavo_by_month[month] += row["amount"]
            continue

        if "RECEITA FEDERAL" in up or "SIMPLES NACIONAL" in up:
            tax_row = dict(row)
            tax_row["tax_type_id"] = int(tax_inss["RecId"]) if 303.0 <= row["amount"] <= 304.0 else int(tax_simple["RecId"])
            taxes.append(tax_row)
            continue

        if "VINDI" in up:
            service_rows.append((vendor_locaweb, code_gateway, row, "Vindi pagamento online (fornecedor Locaweb)"))
            continue

        if abs(row["amount"] - 205.15) < 0.01 or "EVOLUCAO" in up:
            service_rows.append((vendor_evolucao, code_accounting, row, "Contabilidade Evolucao"))
            continue

        if "AMIL" in up or "NOTRE" in up or "INTERMEDICA" in up:
            if "NOTRE" in up or "INTERMEDICA" in up:
                vendor = vendor_notre
            elif month <= 5 and 1700 <= row["amount"] <= 1900:
                vendor = vendor_notre
            else:
                vendor = vendor_amil
            service_rows.append((vendor, code_health, row, "Plano de saude"))
            continue

        if "BOLETO" in up and 1700 <= row["amount"] <= 1900:
            vendor = vendor_notre if month <= 5 else vendor_amil
            service_rows.append((vendor, code_health, row, "Plano de saude"))

    purchases_created = 0
    for vendor, code, row, text in service_rows:
        issue = first_business_day(2025, row["month"])
        due = ymd(row["date"])
        purch_number = f"EXT-2025-{row['month']:02d}-OCR-{int(round(row['amount']*100))}-{row['date'].replace('/', '')}"
        ensure_service_purchase(
            session,
            vendor["VendAccount"],
            purch_number,
            issue,
            due,
            code["RecId"],
            f"{text} - Extrato Jan-Nov/2025 OCR",
            row["amount"],
        )
        purchases_created += 1

    tax_lines = []
    for row in taxes:
        dt = ymd(row["date"])
        tax_name = "INSS" if row["tax_type_id"] == int(tax_inss["RecId"]) else "Simples Nacional"
        tax_lines.append(
            {
                "TransDate": dt,
                "DueDate": dt,
                "Voucher": f"RF{row['date'].replace('/', '')}{int(round(row['amount']*100))}",
                "TaxTypeId": int(row["tax_type_id"]),
                "BankAccountId": int(bank["RecId"]),
                "PaymentMethod": "PIX",
                "PaymentDate": dt,
                "PaidFlag": "1",
                "ReceivedFlag": "0",
                "Description": f"Receita Federal - {tax_name} (OCR)",
                "LedgerCategory": "TAX",
                "AmountCurDebit": row["amount"],
                "AmountCurCredit": 0,
                "PeriodMonth": dt[:7],
                "Status": "P",
                "IsActive": "1",
            }
        )

    if tax_lines:
        ensure_journal(
            session,
            "/api/journals/tax",
            "Extrato Jan-Nov/2025 - Impostos Receita Federal (OCR)",
            "2025-11-30",
            tax_lines,
        )

    payment_lines = []
    for month in sorted(gustavo_by_month.keys()):
        total = round(gustavo_by_month[month], 2)
        if total <= 0:
            continue
        prolabore = min(2760.0, total)
        retirada = round(max(total - prolabore, 0), 2)
        dt = date(2025, month, 28).strftime("%Y-%m-%d")
        payment_lines.append(
            {
                "TransDate": dt,
                "DueDate": dt,
                "Voucher": f"GUS-PROLABORE-2025-{month:02d}",
                "BankAccountId": int(bank["RecId"]),
                "PaymentMethod": "PIX",
                "PaymentDate": dt,
                "PaidFlag": "1",
                "ReceivedFlag": "0",
                "Description": f"Pro-labore Gustavo - 2025/{month:02d} (OCR)",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": prolabore,
                "AmountCurCredit": 0,
                "PeriodMonth": f"2025-{month:02d}",
                "Status": "P",
                "IsActive": "1",
            }
        )
        if retirada > 0:
            payment_lines.append(
                {
                    "TransDate": dt,
                    "DueDate": dt,
                    "Voucher": f"GUS-LUCRO-2025-{month:02d}",
                    "BankAccountId": int(bank["RecId"]),
                    "PaymentMethod": "PIX",
                    "PaymentDate": dt,
                    "PaidFlag": "1",
                    "ReceivedFlag": "0",
                    "Description": f"Retirada de lucro Gustavo - 2025/{month:02d} (OCR)",
                    "LedgerCategory": "OPERATING",
                    "AmountCurDebit": retirada,
                    "AmountCurCredit": 0,
                    "PeriodMonth": f"2025-{month:02d}",
                    "Status": "P",
                    "IsActive": "1",
                }
            )

    if payment_lines:
        ensure_journal(
            session,
            "/api/journals/payment",
            "Extrato Jan-Nov/2025 - Pix Gustavo (OCR)",
            "2025-11-30",
            payment_lines,
        )

    print(f"OCR_ROWS={len(rows)}")
    print(f"SERVICE_ROWS={len(service_rows)}")
    print(f"SERVICE_PURCHASES_UPSERT={purchases_created}")
    print(f"TAX_ROWS={len(taxes)}")
    print(f"GUSTAVO_MONTHS={len(gustavo_by_month)}")


if __name__ == "__main__":
    main()