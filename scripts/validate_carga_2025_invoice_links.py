import requests

BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"

s = requests.Session()
token = s.post(
    f"{BASE_URL}/api/oauth/token",
    json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
)
token.raise_for_status()
token_data = token.json().get("data", {})
s.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

invoices = s.get(f"{BASE_URL}/api/service-invoices").json().get("data", [])
print(f"INVOICES_ACTIVE={len(invoices)}")

receipts = s.get(f"{BASE_URL}/api/journals/receipt").json().get("data", [])
journal = next((x for x in receipts if x.get("Description") == "Carga 2025 - Faturamento Recebido"), None)
if not journal:
    print("RECEIPT_JOURNAL_NOT_FOUND")
    raise SystemExit(0)

detail = s.get(f"{BASE_URL}/api/journals/receipt/{journal['RecId']}").json().get("data", {})
lines = detail.get("Lines", [])
linked = sum(1 for l in lines if l.get("ServiceInvoiceRecId"))
with_cust = sum(1 for l in lines if l.get("CustAccount"))

print(f"RECEIPT_JOURNAL={journal.get('JournalBatchNumber')}")
print(f"LINES_TOTAL={len(lines)}")
print(f"LINES_LINKED={linked}")
print(f"LINES_WITH_CUSTACCOUNT={with_cust}")
