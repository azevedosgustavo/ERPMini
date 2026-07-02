import math
import re
from datetime import datetime

import pandas as pd
import requests

BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"
XLSX_PATH = "Initial Load/Carga 2025.xlsx"
LINK_FILE = "scripts/carga_2025_invoice_links.json"


def api_get(session, path):
    r = session.get(f"{BASE_URL}{path}")
    r.raise_for_status()
    return r.json().get("data", [])


def api_post(session, path, payload):
    r = session.post(f"{BASE_URL}{path}", json=payload)
    r.raise_for_status()
    return r.json().get("data", {})


def normalize_text(v):
    return re.sub(r"\s+", " ", str(v or "")).strip()


def normalize_key(v):
    return re.sub(r"[^A-Z0-9]", "", normalize_text(v).upper())


def norm_date(value):
    if pd.isna(value):
        return datetime.now().strftime("%Y-%m-%d")
    if isinstance(value, datetime):
        return value.strftime("%Y-%m-%d")
    txt = normalize_text(value)
    if "/" in txt:
        return datetime.strptime(txt, "%d/%m/%Y").strftime("%Y-%m-%d")
    return txt[:10]


def to_amount(value):
    if pd.isna(value):
        return 0.0
    return round(abs(float(value)), 2)


def to_int_or_none(value):
    if pd.isna(value):
        return None
    try:
        n = float(value)
        if math.isnan(n) or n <= 0:
            return None
        return int(n)
    except Exception:
        return None


def ensure_customer(session, customers, name, taxid_raw, mercado_externo):
    name_norm = normalize_key(name)
    taxid = normalize_text(taxid_raw)
    if taxid.upper() == "EXTERIOR":
        taxid = ""

    for c in customers:
        c_tax = normalize_text(c.get("TaxId", ""))
        if taxid and c_tax and c_tax.upper() == taxid.upper():
            return c

    for c in customers:
        if name_norm and normalize_key(c.get("Name", "")) == name_norm:
            return c

    is_foreign = mercado_externo > 0 or taxid == ""
    payload = {
        "Party": {
            "PartyType": "O",
            "Name": normalize_text(name),
            "Alias": normalize_key(name)[:20] or "CUSTOMER",
            "TaxId": taxid if taxid else ("US-FOREIGN" if is_foreign else ""),
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
                    "Country": "USA" if is_foreign else "BRASIL",
                    "IsPrimary": "1",
                }
            ],
            "Contacts": [],
        },
        "CompanyType": "ESTRANGEIRA" if is_foreign else "JURIDICA",
        "CurrencyCode": "USD" if is_foreign else "BRL",
        "CreditLimit": 0,
        "PaymentTermDays": 15,
        "CustomerGroup": "FOREIGN" if is_foreign else "DEFAULT",
        "IsActive": "1",
        "IsBlocked": "0",
        "ContactPersons": [],
    }
    rec = api_post(session, "/api/customers", payload)
    updated = api_get(session, "/api/customers")
    created = next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))
    customers[:] = updated
    return created


def normalize_service_key(raw):
    txt = normalize_text(raw)
    txt = txt.replace(" ", "")
    txt = txt.replace("/", "/")
    return txt


def ensure_service_code(session, service_codes, service_key, default_price):
    normalized_target = normalize_service_key(service_key)
    for s in service_codes:
        name = normalize_service_key(s.get("Name", ""))
        desc = normalize_service_key(s.get("Description", ""))
        if normalized_target and (normalized_target in name or normalized_target in desc):
            return s

    payload = {
        "Name": f"Carga 2025 - {normalized_target}",
        "Description": f"Importação Carga 2025 para serviço {normalized_target}",
        "DefaultPrice": default_price,
        "IsActive": "1",
        "IsBlocked": "0",
    }
    rec = api_post(session, "/api/service-codes", payload)
    updated = api_get(session, "/api/service-codes")
    created = next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))
    service_codes[:] = updated
    return created


def build_invoice_number(nfs, inv, idx, issue_date):
    if nfs:
        return str(nfs)
    if inv:
        return f"EXT-{inv}"
    return f"C2025-{issue_date.replace('-', '')}-{idx + 1}"


def build_voucher(nfs, inv, idx, issue_date):
    if nfs:
        return f"FAT-NFS-{nfs}"
    if inv:
        return f"FAT-INV-{inv}"
    return f"FAT-{issue_date.replace('-', '')}-{idx + 1}"


def main():
    df = pd.read_excel(XLSX_PATH, sheet_name="Faturamento")

    s = requests.Session()
    token = s.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    s.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    customers = api_get(s, "/api/customers")
    service_codes = api_get(s, "/api/service-codes")
    invoices = api_get(s, "/api/service-invoices")
    by_invoice_number = {normalize_text(i.get("InvoiceNumber")): i for i in invoices}

    links = {}
    created = 0
    reused = 0
    skipped = 0

    for idx, row in df.iterrows():
        status = normalize_text(row.get("Status")).upper()
        if status != "FATURADO":
            skipped += 1
            continue

        issue_date = norm_date(row.get("Data de Emissao"))
        amount = to_amount(row.get("Valor"))
        if amount <= 0:
            skipped += 1
            continue

        nfs = to_int_or_none(row.get("Numero da NFS-e"))
        inv = to_int_or_none(row.get("Numero da Invoice"))
        mercado_ext = float(row.get("Mercado Externo") or 0)

        customer = ensure_customer(
            s,
            customers,
            row.get("Nome Cliente"),
            row.get("CNPJ Cliente"),
            mercado_ext,
        )
        service = ensure_service_code(
            s,
            service_codes,
            row.get("Codigo do Servico"),
            amount,
        )

        invoice_number = build_invoice_number(nfs, inv, idx, issue_date)
        voucher = build_voucher(nfs, inv, idx, issue_date)

        invoice = by_invoice_number.get(invoice_number)
        if invoice is None:
            is_external = mercado_ext > 0 or normalize_text(row.get("Natureza da Prestacao")).upper() == "EXTERNO"
            desc = normalize_text(row.get("Descricao do Servico"))
            notes = (
                f"Carga 2025 | Competencia: {normalize_text(row.get('Competencia'))} | "
                f"NFS: {nfs or ''} | Invoice: {inv or ''} | "
                f"Mercado Interno: {float(row.get('Mercado Interno') or 0):.2f} | "
                f"Mercado Externo: {mercado_ext:.2f}"
            )
            payload = {
                "CustAccount": customer["CustAccount"],
                "InvoiceNumber": invoice_number,
                "InvoiceDate": issue_date,
                "DueDate": issue_date,
                "TaxAmount": 0,
                "DeductionAmount": 0,
                "Status": "P",
                "IsActive": "1",
                "IsInternationalReplacement": "1" if is_external else "0",
                "InternationalInvoiceNumber": str(inv or ""),
                "InternationalInvoiceSeries": "US" if is_external else "",
                "InternationalInvoiceDate": issue_date if is_external else None,
                "Notes": notes,
                "Lines": [
                    {
                        "ServiceCodeId": int(service["RecId"]),
                        "Description": desc,
                        "LineAmount": amount,
                    }
                ],
            }
            rec = api_post(s, "/api/service-invoices", payload)
            detail = api_get(s, f"/api/service-invoices/{rec['RecId']}")
            invoice = detail
            by_invoice_number[invoice_number] = invoice
            created += 1
        else:
            reused += 1

        links[voucher] = {
            "ServiceInvoiceRecId": int(invoice["RecId"]),
            "CustAccount": str(invoice.get("CustAccount") or customer["CustAccount"]),
            "InvoiceNumber": invoice_number,
        }

    import json

    with open(LINK_FILE, "w", encoding="utf-8") as f:
        json.dump(links, f, ensure_ascii=False, indent=2)

    print(f"FATURAMENTO_TOTAL={len(df)}")
    print(f"INVOICES_CREATED={created}")
    print(f"INVOICES_REUSED={reused}")
    print(f"ROWS_SKIPPED={skipped}")
    print(f"LINKS_GENERATED={len(links)}")
    print(f"LINK_FILE={LINK_FILE}")


if __name__ == "__main__":
    main()
