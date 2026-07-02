import requests
import os
from datetime import datetime
from dotenv import load_dotenv

# Carregar variáveis de ambiente do arquivo .env
load_dotenv()

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8080")
LOGIN_EMAIL = os.getenv("SMOKE_TEST_EMAIL", "admin@admin")
LOGIN_PASSWORD = os.getenv("SMOKE_TEST_PASSWORD", "admin")


def api_get(session, path):
    r = session.get(f"{BASE_URL}{path}")
    r.raise_for_status()
    return r.json().get("data", [])


def api_post(session, path, payload):
    r = session.post(f"{BASE_URL}{path}", json=payload)
    r.raise_for_status()
    return r.json().get("data", {})


def main():
    print("=== SMOKE TEST POSTINGS ===")
    s = requests.Session()
    token = s.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    s.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    companies = api_get(s, "/api/companies")
    banks = api_get(s, "/api/bank-accounts")
    if not companies:
        raise RuntimeError("No active company found for smoke test")

    company = companies[0]
    bank_id = int(banks[0]["RecId"]) if banks else None

    today = datetime.now().strftime("%Y-%m-%d")
    stamp = datetime.now().strftime("%Y%m%d%H%M%S")
    voucher = f"SMOKE-RESET-{stamp}"

    lines = [
        {
            "TransDate": today,
            "DueDate": today,
            "Voucher": voucher,
            "TaxTypeId": None,
            "BankAccountId": bank_id,
            "VendAccount": None,
            "CustAccount": None,
            "ServiceInvoiceRecId": None,
            "PurchRecId": None,
            "PaymentMethod": "PIX",
            "PaymentDate": today,
            "PaidFlag": "1",
            "ReceivedFlag": "0",
            "Description": "Smoke test reset postings",
            "LedgerCategory": "OPERATING",
            "AmountCurDebit": 1.00,
            "AmountCurCredit": 0,
            "PeriodMonth": today[:7],
            "Status": "P",
            "IsActive": "1",
        }
    ]

    payload = {
        "CompanyRecId": int(company["RecId"]),
        "Description": f"Smoke Test Posting {stamp}",
        "JournalDate": today,
        "Posted": "1",
        "IsActive": "1",
        "Lines": lines,
    }

    created = api_post(s, "/api/journals/payment", payload)
    rec_id = int(created.get("RecId", 0))
    if rec_id <= 0:
        raise RuntimeError("Journal creation failed in smoke test")

    detail = api_get(s, f"/api/journals/payment/{rec_id}")
    line_vouchers = [str(x.get("Voucher", "")) for x in detail.get("Lines", [])]
    ok = voucher in line_vouchers

    print(f"SMOKE_JOURNAL_RECID={rec_id}")
    print(f"SMOKE_VOUCHER={voucher}")
    print(f"SMOKE_RESULT={'PASS' if ok else 'FAIL'}")

    if not ok:
        raise RuntimeError("Smoke journal created but voucher not found in detail lines")


if __name__ == "__main__":
    main()
