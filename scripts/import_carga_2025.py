import math
from datetime import datetime

import pandas as pd
import requests

BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"
XLSX_PATH = "Initial Load/Carga 2025.xlsx"


def api_get(session, path):
    r = session.get(f"{BASE_URL}{path}")
    r.raise_for_status()
    return r.json().get("data", [])


def api_post(session, path, payload):
    r = session.post(f"{BASE_URL}{path}", json=payload)
    r.raise_for_status()
    return r.json().get("data", {})


def norm_date(value):
    if pd.isna(value):
        return datetime.now().strftime("%Y-%m-%d")
    if isinstance(value, datetime):
        return value.strftime("%Y-%m-%d")
    txt = str(value).strip()
    if not txt:
        return datetime.now().strftime("%Y-%m-%d")
    # Accept DD/MM/YYYY and YYYY-MM-DD
    if "/" in txt:
        d = datetime.strptime(txt, "%d/%m/%Y")
        return d.strftime("%Y-%m-%d")
    return txt[:10]


def to_amount(value):
    if pd.isna(value):
        return 0.0
    return round(abs(float(value)), 2)


def to_int_or_none(value):
    if pd.isna(value):
        return None
    try:
        f = float(value)
        if math.isnan(f) or f <= 0:
            return None
        return int(f)
    except Exception:
        return None


def ensure_bank(session):
    rows = api_get(session, "/api/bank-accounts")
    if rows:
        return int(rows[0]["RecId"])
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
    return int(rec["RecId"])


def ensure_tax_type(session, name):
    rows = api_get(session, "/api/tax-types")
    for row in rows:
        if str(row.get("Name", "")).strip().upper() == name.upper():
            return int(row["RecId"])
    rec = api_post(
        session,
        "/api/tax-types",
        {
            "Name": name,
            "Description": name,
            "IsActive": "1",
            "IsBlocked": "0",
        },
    )
    return int(rec["RecId"])


def collect_existing_vouchers(session):
    vouchers = set()
    route_pairs = [
        ("/api/journals", "/api/journals"),
        ("/api/journals/receipt", "/api/journals/receipt"),
        ("/api/journals/payment", "/api/journals/payment"),
        ("/api/journals/tax", "/api/journals/tax"),
    ]

    for list_route, detail_prefix in route_pairs:
        journals = api_get(session, list_route)
        for j in journals:
            if str(j.get("IsActive", "1")) != "1":
                continue
            recid = j.get("RecId")
            if not recid:
                continue
            d = session.get(f"{BASE_URL}{detail_prefix}/{recid}")
            if d.status_code == 404:
                continue
            d.raise_for_status()
            lines = d.json().get("data", {}).get("Lines", [])
            for line in lines:
                if str(line.get("IsActive", "1")) != "1":
                    continue
                v = str(line.get("Voucher", "")).strip()
                if v:
                    vouchers.add(v)
    return vouchers


def map_expense_category(raw):
    txt = str(raw or "").upper()
    if "TAX" in txt or "IMPOSTO" in txt:
        return "TAX"
    if "BANK_FEE" in txt or "TARIFA" in txt:
        return "BANK_FEE"
    if "IOF" in txt:
        return "IOF"
    if "PROFIT_WITHDRAWAL" in txt:
        return "PROFIT_WITHDRAWAL"
    if "OPERATING" in txt or "OPERATIONG" in txt:
        return "OPERATING"
    return "MISC_EXPENSE"


def build_revenue_description(row):
    nfs = to_int_or_none(row.get("Numero da NFS-e"))
    inv = to_int_or_none(row.get("Numero da Invoice"))
    mercado_int = float(row.get("Mercado Interno") or 0)
    mercado_ext = float(row.get("Mercado Externo") or 0)
    mercado = "Externo" if mercado_ext > 0 and mercado_int == 0 else "Interno"

    parts = [
        f"Cliente: {str(row.get('Nome Cliente') or '').strip()}",
        f"Serviço: {str(row.get('Descricao do Servico') or '').strip()}",
        f"Mercado: {mercado}",
    ]
    if nfs:
        parts.append(f"NFS-e {nfs}")
    if inv:
        parts.append(f"Invoice {inv}")

    return " | ".join(parts)[:255]


def main():
    faturamento = pd.read_excel(XLSX_PATH, sheet_name="Faturamento")
    despesas = pd.read_excel(XLSX_PATH, sheet_name="Despesas")

    s = requests.Session()
    token = s.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    s.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    bank_id = ensure_bank(s)
    tax_inss = ensure_tax_type(s, "INSS")
    tax_simples = ensure_tax_type(s, "Simples Nacional")

    existing = collect_existing_vouchers(s)

    receipt_lines = []
    created_receipt = 0
    skipped_receipt = 0

    for idx, row in faturamento.iterrows():
        status = str(row.get("Status") or "").strip().upper()
        if status and status != "FATURADO":
            continue

        amount = to_amount(row.get("Valor"))
        if amount <= 0:
            continue

        issue_date = norm_date(row.get("Data de Emissao"))
        nfs = to_int_or_none(row.get("Numero da NFS-e"))
        inv = to_int_or_none(row.get("Numero da Invoice"))

        if nfs:
            voucher = f"FAT-NFS-{nfs}"
        elif inv:
            voucher = f"FAT-INV-{inv}"
        else:
            voucher = f"FAT-{issue_date.replace('-', '')}-{idx + 1}"

        if voucher in existing:
            skipped_receipt += 1
            continue

        receipt_lines.append(
            {
                "TransDate": issue_date,
                "DueDate": issue_date,
                "Voucher": voucher,
                "TaxTypeId": None,
                "BankAccountId": bank_id,
                "VendAccount": None,
                "CustAccount": None,
                "ServiceInvoiceRecId": None,
                "PurchRecId": None,
                "PaymentMethod": "TRANSFER",
                "PaymentDate": issue_date,
                "PaidFlag": "0",
                "ReceivedFlag": "1",
                "Description": build_revenue_description(row),
                "LedgerCategory": "RECEIPT",
                "AmountCurDebit": 0,
                "AmountCurCredit": amount,
                "Status": "P",
                "IsActive": "1",
            }
        )
        existing.add(voucher)
        created_receipt += 1

    payment_lines = []
    tax_lines = []
    created_expenses = 0
    skipped_expenses = 0

    for idx, row in despesas.iterrows():
        status = str(row.get("Status") or "").strip().upper()
        if status and status != "PAGO":
            continue

        amount = to_amount(row.get("Valor"))
        if amount <= 0:
            continue

        trans_date = norm_date(row.get("Data de Lançamento"))
        cat = map_expense_category(row.get("Categoria"))
        obs = str(row.get("Observação") or "").strip()
        desc = str(row.get("Descrição Lançamento") or "").strip()

        voucher = f"DES-{cat}-{trans_date.replace('-', '')}-{idx + 1}"
        if voucher in existing:
            skipped_expenses += 1
            continue

        line = {
            "TransDate": trans_date,
            "DueDate": trans_date,
            "Voucher": voucher,
            "TaxTypeId": None,
            "BankAccountId": bank_id,
            "VendAccount": None,
            "CustAccount": None,
            "ServiceInvoiceRecId": None,
            "PurchRecId": None,
            "PaymentMethod": "PIX",
            "PaymentDate": trans_date,
            "PaidFlag": "1",
            "ReceivedFlag": "0",
            "Description": (f"{desc}" + (f" | Obs: {obs}" if obs else ""))[:255],
            "LedgerCategory": cat,
            "AmountCurDebit": amount,
            "AmountCurCredit": 0,
            "Status": "P",
            "IsActive": "1",
        }

        if cat == "TAX":
            if "INSS" in obs.upper():
                line["TaxTypeId"] = tax_inss
            elif "SIMPLES" in obs.upper():
                line["TaxTypeId"] = tax_simples
            tax_lines.append(line)
        else:
            payment_lines.append(line)

        existing.add(voucher)
        created_expenses += 1

    if receipt_lines:
        api_post(
            s,
            "/api/journals/receipt",
            {
                "Description": "Carga 2025 - Faturamento Recebido",
                "JournalDate": "2025-12-31",
                "Posted": "1",
                "IsActive": "1",
                "Lines": receipt_lines,
            },
        )

    if payment_lines:
        api_post(
            s,
            "/api/journals/payment",
            {
                "Description": "Carga 2025 - Despesas Pagas",
                "JournalDate": "2025-12-31",
                "Posted": "1",
                "IsActive": "1",
                "Lines": payment_lines,
            },
        )

    if tax_lines:
        api_post(
            s,
            "/api/journals/tax",
            {
                "Description": "Carga 2025 - Despesas de Impostos",
                "JournalDate": "2025-12-31",
                "Posted": "1",
                "IsActive": "1",
                "Lines": tax_lines,
            },
        )

    print(f"FATURAMENTO_ROWS={len(faturamento)}")
    print(f"FATURAMENTO_IMPORTED={created_receipt}")
    print(f"FATURAMENTO_SKIPPED={skipped_receipt}")
    print(f"DESPESAS_ROWS={len(despesas)}")
    print(f"DESPESAS_IMPORTED={created_expenses}")
    print(f"DESPESAS_SKIPPED={skipped_expenses}")
    print(f"RECEIPT_LINES={len(receipt_lines)}")
    print(f"PAYMENT_LINES={len(payment_lines)}")
    print(f"TAX_LINES={len(tax_lines)}")


if __name__ == "__main__":
    main()
