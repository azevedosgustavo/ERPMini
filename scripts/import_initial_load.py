import base64
import csv
import glob
import os
import re
from datetime import datetime

import requests
from pypdf import PdfReader


BASE_URL = os.environ.get("ERP_BASE_URL", "http://127.0.0.1:8080")
LOGIN_EMAIL = os.environ.get("ERP_LOGIN_EMAIL", "admin@caspti.local")
LOGIN_PASSWORD = os.environ.get("ERP_LOGIN_PASSWORD", "Admin@123")
INITIAL_LOAD_DIR = os.environ.get("ERP_INITIAL_LOAD_DIR", "Initial Load")
MAPPING_FILE = os.path.join(INITIAL_LOAD_DIR, "initial_load_nfse_service_mapping.csv")

PROVIDER_TAXID = "14.669.892/0001-22"


SERVICE_NAME_TO_KEY = {
    "NFS 14.01/951180002 - Manutencao de equipamentos TI": "14.01/951180002",
    "NFS 1.06/620400002 - Consultoria em TI": "1.06/620400002",
    "NFS 1.07/620910001 - Instalacao e configuracao TI": "1.07/620910001",
    "NFS 1.07/951180001 - Suporte e manutencao TI": "1.07/951180001",
    "NFS 1.05/620310002 - Licenciamento de software": "1.05/620310002",
}


def normalize_whitespace(value):
    return re.sub(r"\s+", " ", value or "").strip()


def parse_decimal(value):
    text = normalize_whitespace(str(value))
    if text == "":
        return 0.0
    text = text.replace("R$", "").replace(" ", "")
    if "," in text and "." in text:
        text = text.replace(".", "").replace(",", ".")
    elif "," in text:
        text = text.replace(",", ".")
    try:
        return round(float(text), 2)
    except ValueError:
        return 0.0


def build_day_15(date_str):
    dt = datetime.strptime(date_str, "%d/%m/%Y")
    return dt.strftime("%Y-%m-15")


def safe_alias(name):
    raw = re.sub(r"[^A-Za-z0-9]", "", name.upper())
    if raw == "":
        raw = "CUSTOMER"
    return raw[:20]


def extract_customer_name(pdf_text):
    # Heuristic: customer block sits between provider email and service code line.
    match = re.search(r"@[^\s]+\s*(.*?)\s*\d{1,2}\.\d{2}\s*/\s*\d+", pdf_text, re.S)
    if not match:
        return "Cliente sem nome"

    candidates = [line.strip() for line in match.group(1).splitlines() if line.strip()]
    for line in candidates:
        if " - " in line:
            continue
        if "CEP" in line.upper() or "@" in line:
            continue
        if len(re.findall(r"[A-Za-z]", line)) < 4:
            continue
        return normalize_whitespace(line)

    return "Cliente sem nome"


def extract_customer_taxid(pdf_text):
    cnpjs = re.findall(r"\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}", pdf_text)
    if not cnpjs:
        return ""
    if len(cnpjs) == 1:
        return "" if cnpjs[0] == PROVIDER_TAXID else cnpjs[0]
    # Last CNPJ is usually the tomador in this PDF layout.
    taxid = cnpjs[-1]
    return "" if taxid == PROVIDER_TAXID else taxid


def normalize_customer_key(name):
    text = normalize_whitespace(name).upper()
    if "FUSION FLOW" in text:
        return "FUSION FLOW SOFTWARE"
    return text


def load_mapping_rows():
    if not os.path.exists(MAPPING_FILE):
        raise RuntimeError(f"Mapping file not found: {MAPPING_FILE}")

    rows = []
    with open(MAPPING_FILE, "r", encoding="utf-8") as handle:
        reader = csv.DictReader(handle)
        for row in reader:
            rows.append(row)
    return rows


def load_pdf_cache():
    cache = {}
    for file_path in sorted(glob.glob(os.path.join(INITIAL_LOAD_DIR, "*.pdf"))):
        reader = PdfReader(file_path)
        text = "\n".join((page.extract_text() or "") for page in reader.pages)
        cache[os.path.basename(file_path)] = {
            "path": file_path,
            "text": text,
            "customer_name": extract_customer_name(text),
            "customer_taxid": extract_customer_taxid(text),
        }
    return cache


def api_get(session, path):
    response = session.get(f"{BASE_URL}{path}")
    response.raise_for_status()
    payload = response.json()
    return payload.get("data")


def api_post(session, path, payload):
    response = session.post(f"{BASE_URL}{path}", json=payload)
    response.raise_for_status()
    data = response.json().get("data", {})
    return data


def ensure_customers(session, pdf_cache):
    customers = api_get(session, "/api/customers")
    by_taxid = {}
    by_name = {}
    for customer in customers:
        if customer.get("TaxId"):
            by_taxid[customer["TaxId"].strip().upper()] = customer
        by_name[normalize_customer_key(customer.get("Name", ""))] = customer

    created = []
    resolved = {}

    for data in pdf_cache.values():
        key_name = normalize_customer_key(data["customer_name"])
        taxid = (data["customer_taxid"] or "").strip().upper()
        customer = None

        if taxid and taxid in by_taxid:
            customer = by_taxid[taxid]
        elif key_name in by_name:
            customer = by_name[key_name]

        if customer is None:
            country = "USA" if "FUSION FLOW" in key_name else "BRASIL"
            company_type = "ESTRANGEIRA" if country == "USA" else "JURIDICA"
            currency = "USD" if country == "USA" else "BRL"
            payload = {
                "Party": {
                    "PartyType": "O",
                    "Name": data["customer_name"],
                    "Alias": safe_alias(data["customer_name"]),
                    "TaxId": taxid if taxid else ("US-FOREIGN" if country == "USA" else ""),
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
                            "Country": country,
                            "IsPrimary": "1",
                        }
                    ],
                    "Contacts": [],
                },
                "CompanyType": company_type,
                "CurrencyCode": currency,
                "CreditLimit": 0,
                "PaymentTermDays": 15,
                "CustomerGroup": "DEFAULT",
                "IsActive": "1",
                "IsBlocked": "0",
                "ContactPersons": [],
            }
            rec = api_post(session, "/api/customers", payload)
            customers = api_get(session, "/api/customers")
            customer = next(item for item in customers if int(item["RecId"]) == int(rec["RecId"]))
            created.append(customer)
            if customer.get("TaxId"):
                by_taxid[customer["TaxId"].strip().upper()] = customer
            by_name[normalize_customer_key(customer.get("Name", ""))] = customer

        resolved[key_name] = customer

    return resolved, created


def ensure_invoices_and_attachments(session, rows, pdf_cache, customers_by_name):
    service_codes = api_get(session, "/api/service-codes")
    service_by_key = {}
    for row in service_codes:
        key = SERVICE_NAME_TO_KEY.get(row.get("Name", ""))
        if key:
            service_by_key[key] = row

    existing_invoices = api_get(session, "/api/service-invoices")
    invoice_by_number = {str(item.get("InvoiceNumber", "")).strip(): item for item in existing_invoices}

    created_invoices = []
    attached_files = []

    for row in rows:
        file_name = row["file_name"]
        if file_name not in pdf_cache:
            continue

        pdf_info = pdf_cache[file_name]
        customer = customers_by_name[normalize_customer_key(pdf_info["customer_name"])]
        service_key = row.get("service_key", "")
        service = service_by_key.get(service_key)
        if not service:
            continue

        invoice_number = str(row.get("nfs_number", "")).strip()
        if invoice_number == "":
            continue

        issue_date = row.get("issue_date", "")
        if issue_date == "":
            continue
        day15 = build_day_15(issue_date)

        amount = parse_decimal(row.get("total_amount_pdf", ""))
        if amount <= 0:
            amount = parse_decimal(service.get("DefaultPrice", 0))
        if amount <= 0:
            amount = 1.0

        description = row.get("suggested_nfse_description", "").strip()
        if description == "":
            description = f"NFS-e {invoice_number}"

        invoice = invoice_by_number.get(invoice_number)
        if invoice is None:
            payload = {
                "CustAccount": customer["CustAccount"],
                "InvoiceNumber": invoice_number,
                "InvoiceDate": datetime.strptime(issue_date, "%d/%m/%Y").strftime("%Y-%m-%d"),
                "DueDate": day15,
                "TaxAmount": 0,
                "DeductionAmount": 0,
                "Status": "O",
                "IsActive": "1",
                "IsInternationalReplacement": "1" if row.get("invoice_reference", "").strip() else "0",
                "InternationalInvoiceNumber": row.get("invoice_reference", "").strip(),
                "InternationalInvoiceSeries": "US" if row.get("invoice_reference", "").strip() else "",
                "InternationalInvoiceDate": None,
                "Notes": file_name,
                "Lines": [
                    {
                        "ServiceCodeId": int(service["RecId"]),
                        "Description": description,
                        "LineAmount": amount,
                    }
                ],
            }
            rec = api_post(session, "/api/service-invoices", payload)
            detail = api_get(session, f"/api/service-invoices/{rec['RecId']}")
            invoice = detail
            invoice_by_number[invoice_number] = invoice
            created_invoices.append(invoice)
        else:
            detail = api_get(session, f"/api/service-invoices/{invoice['RecId']}")
            invoice = detail

        existing_attachments = api_get(
            session,
            f"/api/attachments?entity=CustInvoiceJour&recordId={invoice['RecId']}&lineEntity=&lineId=0",
        )
        file_already_attached = any(item.get("FileName") == file_name for item in existing_attachments)
        if not file_already_attached:
            with open(pdf_info["path"], "rb") as file_handle:
                content = base64.b64encode(file_handle.read()).decode("ascii")
            attachment_payload = {
                "EntityName": "CustInvoiceJour",
                "RecordRecId": int(invoice["RecId"]),
                "LineEntityName": "",
                "LineRecId": 0,
                "FileName": file_name,
                "MimeType": "application/pdf",
                "FileContentBase64": content,
                "Notes": "Initial Load",
            }
            api_post(session, "/api/attachments", attachment_payload)
            attached_files.append(file_name)

    return created_invoices, attached_files, invoice_by_number


def ensure_receipt_journal(session, invoice_by_number):
    invoices = sorted(invoice_by_number.values(), key=lambda item: str(item.get("InvoiceNumber", "")))
    if not invoices:
        return None, []

    lines = []
    for invoice in invoices:
        inv_date = invoice.get("InvoiceDate", "")[:10]
        if not inv_date:
            continue
        day15 = datetime.strptime(inv_date, "%Y-%m-%d").strftime("%Y-%m-15")
        amount = parse_decimal(invoice.get("TotalAmount", 0))
        lines.append(
            {
                "TransDate": day15,
                "DueDate": day15,
                "Voucher": invoice.get("InvoiceNumber", ""),
                "CustAccount": invoice.get("CustAccount", ""),
                "ServiceInvoiceRecId": int(invoice["RecId"]),
                "PaymentMethod": "TRANSFER",
                "PaymentDate": day15,
                "PaidFlag": "1",
                "ReceivedFlag": "1",
                "Description": f"Recebimento NFS-e {invoice.get('InvoiceNumber', '')}",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": 0,
                "AmountCurCredit": amount,
                "PeriodMonth": day15[:7],
                "Status": "P",
                "IsActive": "1",
            }
        )

    # Remove previous auto-generated journal to keep idempotent updates.
    existing_receipts = api_get(session, "/api/journals/receipt")
    for journal in existing_receipts:
        if journal.get("Description") == "Initial Load Receipts" and journal.get("Posted") != "1":
            session.delete(f"{BASE_URL}/api/journals/receipt/{journal['RecId']}").raise_for_status()

    payload = {
        "Description": "Initial Load Receipts",
        "JournalDate": datetime.now().strftime("%Y-%m-%d"),
        "Posted": "1",
        "IsActive": "1",
        "Lines": lines,
    }
    rec = api_post(session, "/api/journals/receipt", payload)
    detail = api_get(session, f"/api/journals/receipt/{rec['RecId']}")
    return detail, lines


def main():
    rows = load_mapping_rows()
    pdf_cache = load_pdf_cache()

    session = requests.Session()
    token_response = session.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token_response.raise_for_status()
    token_data = token_response.json().get("data", {})
    session.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    customers_by_name, created_customers = ensure_customers(session, pdf_cache)
    created_invoices, attached_files, invoice_by_number = ensure_invoices_and_attachments(
        session, rows, pdf_cache, customers_by_name
    )
    journal, journal_lines = ensure_receipt_journal(session, invoice_by_number)

    print(f"CUSTOMERS_CREATED={len(created_customers)}")
    print(f"INVOICES_TOTAL={len(invoice_by_number)}")
    print(f"INVOICES_CREATED={len(created_invoices)}")
    print(f"ATTACHMENTS_ADDED={len(attached_files)}")
    print(f"RECEIPT_JOURNAL_ID={journal['RecId'] if journal else ''}")
    print(f"RECEIPT_LINES={len(journal_lines)}")


if __name__ == "__main__":
    main()