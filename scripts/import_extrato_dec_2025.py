import re
from datetime import date

import requests
from pypdf import PdfReader


BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"
PDF_PATH = "Initial Load/Extrato - Dez. 2025.pdf"


def parse_amount(text):
    raw = text.replace(".", "").replace(",", ".")
    return round(float(raw), 2)


def ymd(day_month, year=2025):
    day, month = [int(part) for part in day_month.split("/")]
    return date(year, month, day).strftime("%Y-%m-%d")


def first_business_day(year, month):
    d = date(year, month, 1)
    while d.weekday() >= 5:
        d = date.fromordinal(d.toordinal() + 1)
    return d.strftime("%Y-%m-%d")


def read_outflows_from_pdf(pdf_path):
    text = "\n".join((page.extract_text() or "") for page in PdfReader(pdf_path).pages)
    compact = re.sub(r"\s+", " ", text)
    pattern = re.compile(
        r"(\d{2}/\d{2})\s+(\d{2}/\d{2})\s+(Saída PIX|Pagamento)\s+(.+?)\s+-R\$\s*([0-9\.,]+)",
        re.IGNORECASE,
    )
    rows = []
    for match in pattern.finditer(compact):
        launch_date, accounting_date, movement_type, description, amount = match.groups()
        rows.append(
            {
                "launch_date": launch_date,
                "accounting_date": accounting_date,
                "movement_type": movement_type,
                "description": description.strip(),
                "amount": parse_amount(amount),
            }
        )
    return rows


def api_get(session, path):
    response = session.get(f"{BASE_URL}{path}")
    response.raise_for_status()
    return response.json().get("data", [])


def api_post(session, path, payload):
    response = session.post(f"{BASE_URL}{path}", json=payload)
    response.raise_for_status()
    return response.json().get("data", {})


def ensure_service_code(session, name, description, default_price):
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
            "DefaultPrice": default_price,
            "IsActive": "1",
            "IsBlocked": "0",
        },
    )
    updated = api_get(session, "/api/service-codes")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_vendor(session, name, taxid="", city="", state="", country="BRASIL"):
    current = api_get(session, "/api/vendors")
    for row in current:
        if row.get("Name", "").strip().upper() == name.strip().upper():
            return row

    alias = re.sub(r"[^A-Za-z0-9]", "", name.upper())[:20] or "VENDOR"
    payload = {
        "Party": {
            "PartyType": "O",
            "Name": name,
            "Alias": alias,
            "TaxId": taxid,
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
                    "City": city,
                    "State": state,
                    "Country": country,
                    "IsPrimary": "1",
                }
            ],
            "Contacts": [],
        },
        "CompanyType": "JURIDICA" if country == "BRASIL" else "ESTRANGEIRA",
        "CurrencyCode": "BRL",
        "PaymentTermDays": 0,
        "VendorGroup": "DEFAULT",
        "IsActive": "1",
        "IsBlocked": "0",
        "ContactPersons": [],
    }
    rec = api_post(session, "/api/vendors", payload)
    updated = api_get(session, "/api/vendors")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_bank_account(session):
    current = api_get(session, "/api/bank-accounts")
    for row in current:
        if row.get("BankName", "").strip().upper() == "C6 BANK" and row.get("AccountNumber") in ["4701708", "47017082"]:
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
    updated = api_get(session, "/api/bank-accounts")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_service_purchase(session, vend_account, purch_number, issue_date, due_date, service_code_id, description, amount):
    current = api_get(session, "/api/purchase-orders/services")
    for row in current:
        if str(row.get("PurchNumber", "")).strip() == purch_number:
            return row

    rec = api_post(
        session,
        "/api/purchase-orders/services",
        {
            "VendAccount": vend_account,
            "PurchNumber": purch_number,
            "PurchDate": issue_date,
            "DueDate": due_date,
            "Status": "P",
            "DeductionAmount": 0,
            "Notes": "Extrato C6 Dez/2025",
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
    return rec


def ensure_payment_journal(session, description, journal_date, lines, endpoint):
    current = api_get(session, endpoint)
    for row in current:
        if str(row.get("Description", "")).strip() == description:
            return row

    rec = api_post(
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
    return rec


def main():
    rows = read_outflows_from_pdf(PDF_PATH)
    outflows = [row for row in rows if row["amount"] > 0]

    session = requests.Session()
    token = session.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    session.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    # Master data
    vendor_locaweb = ensure_vendor(session, "LOCAWEB SERVICOS DE INTERNET S.A.", "02.351.877/0001-52", "SAO PAULO", "SP", "BRASIL")
    vendor_amil = ensure_vendor(session, "AMIL ASSISTENCIA MEDICA INTERNACIONAL S.A.", "29.309.127/0001-79", "SAO PAULO", "SP", "BRASIL")
    ensure_vendor(session, "NOTRE DAME INTERMEDICA SAUDE S.A.", "44.649.812/0001-38", "SAO PAULO", "SP", "BRASIL")
    vendor_evolucao = ensure_vendor(session, "EVOLUCAO GESTAO EMPRESARIAL LTDA", "", "", "", "BRASIL")

    bank = ensure_bank_account(session)

    code_health = ensure_service_code(session, "Despesa - Plano de Saude", "Tomada de servico de plano de saude", 0)
    code_gateway = ensure_service_code(session, "Despesa - Gateway de Pagamento", "Tomada de servico para pagamento online", 0)
    code_accounting = ensure_service_code(session, "Despesa - Contabilidade", "Tomada de servico de contabilidade", 0)

    # Classify December outflows.
    taxes = []
    gustavo = []
    health = []
    vindi = []
    accounting = []

    for row in outflows:
        desc_up = row["description"].upper()
        if "RECEITA FEDERAL" in desc_up:
            taxes.append(row)
        elif "GUSTAVO" in desc_up:
            gustavo.append(row)
        elif "VINDI PAGAMENTOS ONLINE" in desc_up:
            vindi.append(row)
        elif "AMIL" in desc_up or "NOTRE DAME" in desc_up or "INTERMEDICA" in desc_up:
            health.append(row)
        elif abs(row["amount"] - 205.15) < 0.01 or "EVOLUCAO" in desc_up:
            accounting.append(row)

    # Service purchases (tomada de servico).
    service_purchases_created = 0
    for row in health:
        issue = first_business_day(2025, 12)
        due = ymd(row["accounting_date"])
        vendor = vendor_amil
        purch_number = f"EXT-2025-12-HEALTH-{row['launch_date'].replace('/', '')}-{int(round(row['amount']*100))}"
        ensure_service_purchase(
            session,
            vendor["VendAccount"],
            purch_number,
            issue,
            due,
            code_health["RecId"],
            "Plano de saude - Extrato Dez/2025",
            row["amount"],
        )
        service_purchases_created += 1

    for row in vindi:
        issue = first_business_day(2025, 12)
        due = ymd(row["accounting_date"])
        purch_number = f"EXT-2025-12-VINDI-{row['launch_date'].replace('/', '')}-{int(round(row['amount']*100))}"
        ensure_service_purchase(
            session,
            vendor_locaweb["VendAccount"],
            purch_number,
            issue,
            due,
            code_gateway["RecId"],
            "Vindi pagamento online (fornecedor Locaweb) - Extrato Dez/2025",
            row["amount"],
        )
        service_purchases_created += 1

    for row in accounting:
        issue = first_business_day(2025, 12)
        due = ymd(row["accounting_date"])
        purch_number = f"EXT-2025-12-CONTAB-{row['launch_date'].replace('/', '')}-{int(round(row['amount']*100))}"
        ensure_service_purchase(
            session,
            vendor_evolucao["VendAccount"],
            purch_number,
            issue,
            due,
            code_accounting["RecId"],
            "Contabilidade Evolucao - Extrato Dez/2025",
            row["amount"],
        )
        service_purchases_created += 1

    # Tax journal for Receita Federal outflows.
    tax_lines = []
    for row in taxes:
        tax_type = "INSS" if 303.0 <= row["amount"] <= 304.0 else "Simples Nacional"
        d = ymd(row["accounting_date"])
        tax_lines.append(
            {
                "TransDate": d,
                "DueDate": d,
                "Voucher": f"RF{row['launch_date'].replace('/', '')}",
                "TaxTypeId": None,
                "BankAccountId": int(bank["RecId"]),
                "VendAccount": None,
                "CustAccount": None,
                "ServiceInvoiceRecId": None,
                "PurchRecId": None,
                "PaymentMethod": "PIX",
                "PaymentDate": d,
                "PaidFlag": "1",
                "ReceivedFlag": "0",
                "Description": f"Receita Federal - {tax_type}",
                "LedgerCategory": "TAX",
                "AmountCurDebit": row["amount"],
                "AmountCurCredit": 0,
                "PeriodMonth": d[:7],
                "Status": "P",
                "IsActive": "1",
            }
        )

    if tax_lines:
        ensure_payment_journal(
            session,
            "Extrato Dez/2025 - Impostos Receita Federal",
            "2025-12-12",
            tax_lines,
            "/api/journals/tax",
        )

    # Gustavo monthly split: 2760 pro-labore + remainder retirada de lucro.
    gustavo_total = round(sum(row["amount"] for row in gustavo), 2)
    prolabore = 2760.00 if gustavo_total > 0 else 0.0
    retirada = round(max(gustavo_total - prolabore, 0), 2)

    if gustavo_total > 0:
        month_end = "2025-12-30"
        payment_lines = [
            {
                "TransDate": month_end,
                "DueDate": month_end,
                "Voucher": "GUS-PROLABORE-2025-12",
                "TaxTypeId": None,
                "BankAccountId": int(bank["RecId"]),
                "VendAccount": None,
                "CustAccount": None,
                "ServiceInvoiceRecId": None,
                "PurchRecId": None,
                "PaymentMethod": "PIX",
                "PaymentDate": month_end,
                "PaidFlag": "1",
                "ReceivedFlag": "0",
                "Description": "Pro-labore Gustavo - Dez/2025",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": prolabore,
                "AmountCurCredit": 0,
                "PeriodMonth": "2025-12",
                "Status": "P",
                "IsActive": "1",
            },
            {
                "TransDate": month_end,
                "DueDate": month_end,
                "Voucher": "GUS-LUCRO-2025-12",
                "TaxTypeId": None,
                "BankAccountId": int(bank["RecId"]),
                "VendAccount": None,
                "CustAccount": None,
                "ServiceInvoiceRecId": None,
                "PurchRecId": None,
                "PaymentMethod": "PIX",
                "PaymentDate": month_end,
                "PaidFlag": "1",
                "ReceivedFlag": "0",
                "Description": "Retirada de lucro Gustavo - Dez/2025",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": retirada,
                "AmountCurCredit": 0,
                "PeriodMonth": "2025-12",
                "Status": "P",
                "IsActive": "1",
            },
        ]
        ensure_payment_journal(
            session,
            "Extrato Dez/2025 - Pix Gustavo",
            month_end,
            payment_lines,
            "/api/journals/payment",
        )

    print(f"OUTFLOWS_PARSED={len(outflows)}")
    print(f"TAX_ITEMS={len(taxes)}")
    print(f"HEALTH_ITEMS={len(health)}")
    print(f"VINDI_ITEMS={len(vindi)}")
    print(f"ACCOUNTING_ITEMS={len(accounting)}")
    print(f"SERVICE_PURCHASES_UPSERT={service_purchases_created}")
    print(f"GUSTAVO_TOTAL={gustavo_total:.2f}")
    print(f"GUSTAVO_PROLABORE={prolabore:.2f}")
    print(f"GUSTAVO_RETIRADA_LUCRO={retirada:.2f}")


if __name__ == "__main__":
    main()