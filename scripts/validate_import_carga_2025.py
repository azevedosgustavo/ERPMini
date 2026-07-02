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

for ep, name in [
    ("/api/journals/receipt", "REC"),
    ("/api/journals/payment", "PAY"),
    ("/api/journals/tax", "TAX"),
]:
    rows = s.get(f"{BASE_URL}{ep}").json().get("data", [])
    print(f"{name}_JOURNALS={len(rows)}")
    for r in rows:
        detail = s.get(f"{BASE_URL}{ep}/{r['RecId']}").json().get("data", {})
        lines = detail.get("Lines", [])
        debit = round(sum(float(x.get("AmountCurDebit") or 0) for x in lines), 2)
        credit = round(sum(float(x.get("AmountCurCredit") or 0) for x in lines), 2)
        print(
            f"{name}_JRN={r.get('JournalBatchNumber')} | DESC={r.get('Description')} | LINES={len(lines)} | DEBIT={debit:.2f} | CREDIT={credit:.2f}"
        )
