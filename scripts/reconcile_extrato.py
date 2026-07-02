"""
Reconciliation script: compare bank statement PDFs vs DB and import missing entries.

Strategy:
  - Extract all outflows from both PDFs (same logic as import scripts)
  - Fetch all active LedgerJournalTrans and PurchTable rows from DB via API
  - Compare by voucher/PurchNumber
  - Report what is already present and what is missing
  - Import all missing entries
"""
import re
from collections import defaultdict
from datetime import date

import fitz
import requests
from pypdf import PdfReader
from rapidocr_onnxruntime import RapidOCR

BASE_URL = "http://127.0.0.1:8080"
LOGIN_EMAIL = "admin@caspti.local"
LOGIN_PASSWORD = "Admin@123"

PDF_JAN_NOV = "Initial Load/Extrato - Jan a Nov 2025.pdf"
PDF_DEC = "Initial Load/Extrato - Dez. 2025.pdf"


# ─────────────────────────────────────────────────────────────────────────────
# Utility helpers
# ─────────────────────────────────────────────────────────────────────────────

def parse_amount(value):
    return round(float(value.replace(".", "").replace(",", ".")), 2)


def ymd(day_month, year=2025):
    day, month = [int(p) for p in day_month.split("/")]
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


# ─────────────────────────────────────────────────────────────────────────────
# PDF extraction
# ─────────────────────────────────────────────────────────────────────────────

def extract_jan_nov_outflows(pdf_path):
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
        candidates.append({
            "line": window,
            "description": line,
            "amount": amount,
            "date": accounting_date,
            "month": month,
        })

    unique = {}
    for row in candidates:
        key = (row["date"], row["description"], f"{row['amount']:.2f}")
        unique[key] = row
    return list(unique.values())


def extract_dec_outflows(pdf_path):
    text = "\n".join((page.extract_text() or "") for page in PdfReader(pdf_path).pages)
    compact = re.sub(r"\s+", " ", text)
    pattern = re.compile(
        r"(\d{2}/\d{2})\s+(\d{2}/\d{2})\s+(Saída PIX|Pagamento)\s+(.+?)\s+-R\$\s*([0-9\.,]+)",
        re.IGNORECASE,
    )
    rows = []
    for match in pattern.finditer(compact):
        launch_date, accounting_date, movement_type, description, amount = match.groups()
        a = parse_amount(amount)
        if a > 0:
            rows.append({
                "launch_date": launch_date,
                "accounting_date": accounting_date,
                "movement_type": movement_type,
                "description": description.strip(),
                "amount": a,
            })
    return rows


# ─────────────────────────────────────────────────────────────────────────────
# API helpers
# ─────────────────────────────────────────────────────────────────────────────

def api_get(session, path):
    r = session.get(f"{BASE_URL}{path}")
    r.raise_for_status()
    return r.json().get("data", [])


def api_post(session, path, payload):
    r = session.post(f"{BASE_URL}{path}", json=payload)
    r.raise_for_status()
    return r.json().get("data", {})


def ensure_vendor(session, name, taxid="", city="", state="", country="BRASIL"):
    current = api_get(session, "/api/vendors")
    for row in current:
        if row.get("Name", "").strip().upper() == name.strip().upper():
            return row
    alias = re.sub(r"[^A-Za-z0-9]", "", name.upper())[:20] or "VENDOR"
    rec = api_post(session, "/api/vendors", {
        "Party": {
            "PartyType": "O", "Name": name, "Alias": alias, "TaxId": taxid,
            "IsActive": "1", "IsBlocked": "0",
            "Addresses": [{"AddressType": "B", "ZipCode": "", "Street": "",
                           "StreetNumber": "", "Complement": "", "District": "",
                           "City": city, "State": state, "Country": country, "IsPrimary": "1"}],
            "Contacts": [],
        },
        "CompanyType": "JURIDICA",
        "CurrencyCode": "BRL",
        "PaymentTermDays": 0,
        "VendorGroup": "DEFAULT",
        "IsActive": "1",
        "IsBlocked": "0",
        "ContactPersons": [],
    })
    updated = api_get(session, "/api/vendors")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_service_code(session, name, description):
    current = api_get(session, "/api/service-codes")
    for row in current:
        if row.get("Name") == name:
            return row
    rec = api_post(session, "/api/service-codes", {
        "Name": name, "Description": description,
        "DefaultPrice": 0, "IsActive": "1", "IsBlocked": "0",
    })
    updated = api_get(session, "/api/service-codes")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_bank_account(session):
    current = api_get(session, "/api/bank-accounts")
    for row in current:
        if row.get("BankName", "").strip().upper() == "C6 BANK":
            return row
    rec = api_post(session, "/api/bank-accounts", {
        "BankName": "C6 Bank", "AccountNumber": "4701708", "AccountDigit": "2",
        "Description": "Conta corrente principal C6", "IsActive": "1", "IsBlocked": "0",
    })
    updated = api_get(session, "/api/bank-accounts")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_tax_type(session, name):
    rows = api_get(session, "/api/tax-types")
    for row in rows:
        if row.get("Name", "").strip().upper() == name.strip().upper():
            return row
    rec = api_post(session, "/api/tax-types", {
        "Name": name, "Description": f"{name} - Extrato",
        "IsActive": "1", "IsBlocked": "0",
    })
    updated = api_get(session, "/api/tax-types")
    return next(item for item in updated if int(item["RecId"]) == int(rec["RecId"]))


def ensure_service_purchase(session, vend_account, purch_number, issue_date, due_date,
                            service_code_id, description, amount, notes=""):
    current = api_get(session, "/api/purchase-orders/services")
    for row in current:
        if str(row.get("PurchNumber", "")).strip() == purch_number:
            return False  # already exists
    api_post(session, "/api/purchase-orders/services", {
        "VendAccount": vend_account,
        "PurchNumber": purch_number,
        "PurchDate": issue_date,
        "DueDate": due_date,
        "Status": "P",
        "DeductionAmount": 0,
        "Notes": notes or "Reconciliação extrato",
        "IsActive": "1",
        "Lines": [{
            "ServiceCodeId": int(service_code_id),
            "Description": description,
            "Quantity": 1,
            "UnitPrice": amount,
            "LineAmount": amount,
        }],
    })
    return True  # created


def ensure_journal(session, endpoint, description, journal_date, lines):
    current = api_get(session, endpoint)
    for row in current:
        if str(row.get("Description", "")).strip() == description:
            return False  # already exists
    api_post(session, endpoint, {
        "Description": description,
        "JournalDate": journal_date,
        "Posted": "1",
        "IsActive": "1",
        "Lines": lines,
    })
    return True


def collect_existing_vouchers(session):
    """Collect active vouchers from all active journals (all types)."""
    vouchers = set()

    # Route map: list endpoint -> detail endpoint prefix
    # Generic /api/journals/{id} only supports GEN journals.
    typed_endpoints = [
        ("/api/journals", "/api/journals"),
        ("/api/journals/receipt", "/api/journals/receipt"),
        ("/api/journals/payment", "/api/journals/payment"),
        ("/api/journals/tax", "/api/journals/tax"),
    ]
    for list_endpoint, detail_prefix in typed_endpoints:
        for j in api_get(session, list_endpoint):
            if str(j.get("IsActive", "1")) != "1":
                continue
            recid = j.get("RecId")
            if not recid:
                continue
            detail = session.get(f"{BASE_URL}{detail_prefix}/{recid}")
            if detail.status_code == 404:
                # Skip incompatible route/type combinations.
                continue
            detail.raise_for_status()
            lines = detail.json().get("data", {}).get("Lines", [])
            for line in lines:
                if str(line.get("IsActive", "1")) == "1":
                    v = str(line.get("Voucher", "")).strip()
                    if v:
                        vouchers.add(v)

    return vouchers


# ─────────────────────────────────────────────────────────────────────────────
# Classification helpers (same rules as original import scripts)
# ─────────────────────────────────────────────────────────────────────────────

def classify_jan_nov(row, tax_inss_id, tax_simple_id,
                     vendor_notre, vendor_amil, vendor_locaweb, vendor_evolucao,
                     code_health, code_gateway, code_accounting):
    up = row["description"].upper()
    month = row["month"]

    if "GUSTAVO" in up:
        return ("GUSTAVO", None, None, None)

    if "RECEITA FEDERAL" in up or "SIMPLES NACIONAL" in up:
        tax_id = tax_inss_id if 303.0 <= row["amount"] <= 304.0 else tax_simple_id
        return ("TAX", tax_id, None, None)

    if "VINDI" in up:
        return ("SERVICE", vendor_locaweb, code_gateway, "Vindi pagamento online (Locaweb)")

    if abs(row["amount"] - 205.15) < 0.01 or "EVOLUCAO" in up:
        return ("SERVICE", vendor_evolucao, code_accounting, "Contabilidade Evolucao")

    if "AMIL" in up or "NOTRE" in up or "INTERMEDICA" in up or "BOLETO" in up:
        if "NOTRE" in up or "INTERMEDICA" in up:
            vendor = vendor_notre
        elif month <= 5 and 1700 <= row["amount"] <= 1900:
            vendor = vendor_notre
        else:
            vendor = vendor_amil
        return ("SERVICE", vendor, code_health, "Plano de saude")

    return ("UNKNOWN", None, None, up)


def purch_num_jan_nov(row):
    return f"EXT-2025-{row['month']:02d}-OCR-{int(round(row['amount']*100))}"


def purch_num_dec(row, category):
    slug = {
        "HEALTH": "HEALTH",
        "VINDI": "VINDI",
        "ACCOUNTING": "CONTAB",
    }.get(category, "MISC")
    seq = row["launch_date"].replace("/", "")
    amount_cents = int(round(row["amount"] * 100))
    return f"EXT-2025-12-{slug}-{seq}-{amount_cents}"


def tax_voucher_jan_nov(row):
    return f"RF{row['date'].replace('/', '')}{int(round(row['amount']*100))}"


def tax_voucher_dec(row):
    return f"RF{row['launch_date'].replace('/', '')}"


# ─────────────────────────────────────────────────────────────────────────────
# Main reconciliation
# ─────────────────────────────────────────────────────────────────────────────

def main():
    print("=== RECONCILIATION: Extrato vs Database ===\n")

    # Step 1: Extract from PDFs
    print("[1/4] Extracting PDF Jan-Nov (OCR)...")
    jan_nov_rows = extract_jan_nov_outflows(PDF_JAN_NOV)
    print(f"      {len(jan_nov_rows)} candidate outflow rows found")

    print("[1/4] Extracting PDF Dec (text)...")
    dec_rows = extract_dec_outflows(PDF_DEC)
    print(f"      {len(dec_rows)} outflow rows found\n")

    # Step 2: Fetch current DB state
    print("[2/4] Fetching database state...")
    session = requests.Session()
    token = session.post(
        f"{BASE_URL}/api/oauth/token",
        json={"grant_type": "password", "username": LOGIN_EMAIL, "password": LOGIN_PASSWORD},
    )
    token.raise_for_status()
    token_data = token.json().get("data", {})
    session.headers.update({"Authorization": f"Bearer {token_data.get('access_token', '')}"})

    existing_vouchers = collect_existing_vouchers(session)
    existing_purch = api_get(session, "/api/purchase-orders/services")

    # Build sets of existing purchase numbers
    existing_purch_nums = {str(p.get("PurchNumber", "")).strip() for p in existing_purch}

    print(f"      {len(existing_vouchers)} existing journal vouchers")
    print(f"      {len(existing_purch_nums)} existing purchase orders\n")

    # Step 3: Resolve master data
    print("[3/4] Resolving master data...")
    vendor_locaweb = ensure_vendor(session, "LOCAWEB SERVICOS DE INTERNET S.A.", "02.351.877/0001-52", "SAO PAULO", "SP")
    vendor_amil = ensure_vendor(session, "AMIL ASSISTENCIA MEDICA INTERNACIONAL S.A.", "29.309.127/0001-79", "SAO PAULO", "SP")
    vendor_notre = ensure_vendor(session, "NOTRE DAME INTERMEDICA SAUDE S.A.", "44.649.812/0001-38", "SAO PAULO", "SP")
    vendor_evolucao = ensure_vendor(session, "EVOLUCAO GESTAO EMPRESARIAL LTDA")
    bank = ensure_bank_account(session)
    code_health = ensure_service_code(session, "Despesa - Plano de Saude", "Tomada de servico de plano de saude")
    code_gateway = ensure_service_code(session, "Despesa - Gateway de Pagamento", "Tomada de servico para pagamento online")
    code_accounting = ensure_service_code(session, "Despesa - Contabilidade", "Tomada de servico de contabilidade")
    tax_inss = ensure_tax_type(session, "INSS")
    tax_simple = ensure_tax_type(session, "Simples Nacional")
    print("      Master data ready\n")

    # Step 4: Process Jan-Nov
    print("[4/4] Reconciling Jan-Nov 2025...")
    missing_purch_jan_nov = []
    missing_taxes_jan_nov = []
    gustavo_by_month = defaultdict(float)
    already_purch = 0
    already_tax = 0

    for row in jan_nov_rows:
        kind, a, b, c = classify_jan_nov(
            row,
            int(tax_inss["RecId"]), int(tax_simple["RecId"]),
            vendor_notre, vendor_amil, vendor_locaweb, vendor_evolucao,
            code_health, code_gateway, code_accounting
        )

        if kind == "GUSTAVO":
            gustavo_by_month[row["month"]] += row["amount"]
            continue

        if kind == "TAX":
            voucher = tax_voucher_jan_nov(row)
            if voucher in existing_vouchers:
                already_tax += 1
            else:
                missing_taxes_jan_nov.append({**row, "tax_type_id": a, "voucher": voucher})
            continue

        if kind == "SERVICE":
            vendor, code, desc = a, b, c
            pn = purch_num_jan_nov(row)
            if pn in existing_purch_nums:
                already_purch += 1
            else:
                missing_purch_jan_nov.append({**row, "vendor": vendor, "code": code, "desc": desc, "purch_num": pn})
            continue

    # Build expected Gustavo vouchers Jan-Nov
    gustavo_missing_months = []
    for month, total in sorted(gustavo_by_month.items()):
        pro_voucher = f"GUS-PROLABORE-2025-{month:02d}"
        lucro_voucher = f"GUS-LUCRO-2025-{month:02d}"
        prolabore = min(2760.0, total)
        retirada = round(max(total - prolabore, 0), 2)
        if pro_voucher not in existing_vouchers:
            gustavo_missing_months.append((month, total, prolabore, retirada))
        elif lucro_voucher not in existing_vouchers and retirada > 0:
            gustavo_missing_months.append((month, total, 0, retirada))

    print(f"\n  Jan-Nov summary:")
    print(f"  - Service purchases already in DB: {already_purch}")
    print(f"  - Tax vouchers already in DB:      {already_tax}")
    print(f"  - MISSING service purchases:       {len(missing_purch_jan_nov)}")
    print(f"  - MISSING tax vouchers:            {len(missing_taxes_jan_nov)}")
    print(f"  - MISSING Gustavo months:          {len(gustavo_missing_months)}")

    for row in missing_purch_jan_nov:
        print(f"    MISSING_PURCH  {row['purch_num']}  {row['date']}  R${row['amount']:.2f}  {row['description'][:50]}")
    for row in missing_taxes_jan_nov:
        print(f"    MISSING_TAX    {row['voucher']}  {row['date']}  R${row['amount']:.2f}")
    for (month, total, pro, ret) in gustavo_missing_months:
        print(f"    MISSING_GUSTAVO  2025-{month:02d}  total={total:.2f}  pro={pro:.2f}  lucro={ret:.2f}")

    # Process Dec
    print("\n  Reconciling Dec 2025...")
    dec_outflows = [r for r in dec_rows if r["amount"] > 0]
    missing_purch_dec = []
    missing_taxes_dec = []
    gustavo_dec_total = 0.0
    already_purch_dec = 0
    already_tax_dec = 0

    for row in dec_outflows:
        desc_up = row["description"].upper()
        if "GUSTAVO" in desc_up:
            gustavo_dec_total += row["amount"]
            continue
        if "RECEITA FEDERAL" in desc_up:
            voucher = tax_voucher_dec(row)
            if voucher in existing_vouchers:
                already_tax_dec += 1
            else:
                missing_taxes_dec.append({**row, "voucher": voucher})
            continue
        # Service purchases
        if "VINDI" in desc_up:
            cat, vendor, code = "VINDI", vendor_locaweb, code_gateway
            desc = "Vindi pagamento online (Locaweb) - Dez/2025"
        elif "AMIL" in desc_up or "NOTRE DAME" in desc_up or "INTERMEDICA" in desc_up:
            cat, vendor, code = "HEALTH", vendor_amil, code_health
            desc = "Plano de saude - Dez/2025"
        elif abs(row["amount"] - 205.15) < 0.01 or "EVOLUCAO" in desc_up:
            cat, vendor, code = "ACCOUNTING", vendor_evolucao, code_accounting
            desc = "Contabilidade Evolucao - Dez/2025"
        else:
            continue
        pn = purch_num_dec(row, cat)
        if pn in existing_purch_nums:
            already_purch_dec += 1
        else:
            missing_purch_dec.append({**row, "vendor": vendor, "code": code, "desc": desc, "purch_num": pn, "cat": cat})

    gustavo_dec_missing = False
    if gustavo_dec_total > 0:
        if "GUS-PROLABORE-2025-12" not in existing_vouchers:
            gustavo_dec_missing = True

    print(f"\n  Dec summary:")
    print(f"  - Service purchases already in DB: {already_purch_dec}")
    print(f"  - Tax vouchers already in DB:      {already_tax_dec}")
    print(f"  - MISSING service purchases:       {len(missing_purch_dec)}")
    print(f"  - MISSING tax vouchers:            {len(missing_taxes_dec)}")
    print(f"  - MISSING Gustavo Dec:             {'YES' if gustavo_dec_missing else 'NO'} (total={gustavo_dec_total:.2f})")
    for row in missing_purch_dec:
        print(f"    MISSING_PURCH  {row['purch_num']}  {row['accounting_date']}  R${row['amount']:.2f}  {row['description'][:50]}")
    for row in missing_taxes_dec:
        print(f"    MISSING_TAX    {row['voucher']}  {row['accounting_date']}  R${row['amount']:.2f}")

    # ── IMPORT MISSING ──────────────────────────────────────────────────────
    total_created = 0

    # Missing Jan-Nov service purchases
    for row in missing_purch_jan_nov:
        issue = first_business_day(2025, row["month"])
        due = ymd(row["date"])
        ok = ensure_service_purchase(
            session,
            row["vendor"]["VendAccount"],
            row["purch_num"],
            issue, due,
            row["code"]["RecId"],
            f"{row['desc']} - Extrato Jan-Nov/2025 OCR",
            row["amount"],
            "Reconciliação Jan-Nov/2025",
        )
        if ok:
            print(f"  IMPORTED_PURCH  {row['purch_num']}")
            total_created += 1

    # Missing Jan-Nov taxes → add to existing journal or create new
    if missing_taxes_jan_nov:
        tax_lines = []
        for row in missing_taxes_jan_nov:
            dt = ymd(row["date"])
            tax_name = "INSS" if row["tax_type_id"] == int(tax_inss["RecId"]) else "Simples Nacional"
            tax_lines.append({
                "TransDate": dt, "DueDate": dt,
                "Voucher": row["voucher"],
                "TaxTypeId": row["tax_type_id"],
                "BankAccountId": int(bank["RecId"]),
                "VendAccount": None, "CustAccount": None,
                "ServiceInvoiceRecId": None, "PurchRecId": None,
                "PaymentMethod": "PIX", "PaymentDate": dt,
                "PaidFlag": "1", "ReceivedFlag": "0",
                "Description": f"Receita Federal - {tax_name} (OCR)",
                "LedgerCategory": "TAX",
                "AmountCurDebit": row["amount"], "AmountCurCredit": 0,
                "PeriodMonth": dt[:7], "Status": "P", "IsActive": "1",
            })
        # Try appending to existing journal
        jrn_desc = "Extrato Jan-Nov/2025 - Impostos Receita Federal (OCR)"
        existing_jrns = api_get(session, "/api/journals/tax")
        existing_jrn = next((j for j in existing_jrns if j.get("Description") == jrn_desc), None)
        if existing_jrn:
            # Create a supplementary journal
            jrn_desc2 = f"Extrato Jan-Nov/2025 - Impostos RF (Reconciliação)"
            ok = ensure_journal(session, "/api/journals/tax", jrn_desc2, "2025-11-30", tax_lines)
            if ok:
                print(f"  IMPORTED_TAX_JOURNAL  '{jrn_desc2}'  ({len(tax_lines)} lines)")
                total_created += len(tax_lines)
        else:
            ok = ensure_journal(session, "/api/journals/tax", jrn_desc, "2025-11-30", tax_lines)
            if ok:
                print(f"  IMPORTED_TAX_JOURNAL  '{jrn_desc}'  ({len(tax_lines)} lines)")
                total_created += len(tax_lines)

    # Missing Jan-Nov Gustavo
    if gustavo_missing_months:
        payment_lines = []
        for (month, total, prolabore, retirada) in gustavo_missing_months:
            dt = date(2025, month, 28).strftime("%Y-%m-%d")
            pro_voucher = f"GUS-PROLABORE-2025-{month:02d}"
            lucro_voucher = f"GUS-LUCRO-2025-{month:02d}"
            if prolabore > 0 and pro_voucher not in existing_vouchers:
                payment_lines.append({
                    "TransDate": dt, "DueDate": dt,
                    "Voucher": pro_voucher,
                    "BankAccountId": int(bank["RecId"]),
                    "PaymentMethod": "PIX", "PaymentDate": dt,
                    "PaidFlag": "1", "ReceivedFlag": "0",
                    "Description": f"Pro-labore Gustavo - 2025/{month:02d} (OCR)",
                    "LedgerCategory": "OPERATING",
                    "AmountCurDebit": prolabore, "AmountCurCredit": 0,
                    "PeriodMonth": f"2025-{month:02d}", "Status": "P", "IsActive": "1",
                })
            if retirada > 0 and lucro_voucher not in existing_vouchers:
                payment_lines.append({
                    "TransDate": dt, "DueDate": dt,
                    "Voucher": lucro_voucher,
                    "BankAccountId": int(bank["RecId"]),
                    "PaymentMethod": "PIX", "PaymentDate": dt,
                    "PaidFlag": "1", "ReceivedFlag": "0",
                    "Description": f"Retirada de lucro Gustavo - 2025/{month:02d} (OCR)",
                    "LedgerCategory": "OPERATING",
                    "AmountCurDebit": retirada, "AmountCurCredit": 0,
                    "PeriodMonth": f"2025-{month:02d}", "Status": "P", "IsActive": "1",
                })
        if payment_lines:
            jrn_desc = "Extrato Jan-Nov/2025 - Pix Gustavo (Reconciliação)"
            ok = ensure_journal(session, "/api/journals/payment", jrn_desc, "2025-11-30", payment_lines)
            if ok:
                print(f"  IMPORTED_GUSTAVO_JOURNAL  '{jrn_desc}'  ({len(payment_lines)} lines)")
                total_created += len(payment_lines)

    # Missing Dec service purchases
    for row in missing_purch_dec:
        issue = first_business_day(2025, 12)
        due = ymd(row["accounting_date"])
        ok = ensure_service_purchase(
            session,
            row["vendor"]["VendAccount"],
            row["purch_num"],
            issue, due,
            row["code"]["RecId"],
            row["desc"],
            row["amount"],
            "Reconciliação Dez/2025",
        )
        if ok:
            print(f"  IMPORTED_PURCH  {row['purch_num']}")
            total_created += 1

    # Missing Dec taxes
    if missing_taxes_dec:
        tax_lines = []
        for row in missing_taxes_dec:
            d = ymd(row["accounting_date"])
            tax_type = "INSS" if 303.0 <= row["amount"] <= 304.0 else "Simples Nacional"
            tax_lines.append({
                "TransDate": d, "DueDate": d,
                "Voucher": row["voucher"],
                "TaxTypeId": None,
                "BankAccountId": int(bank["RecId"]),
                "VendAccount": None, "CustAccount": None,
                "ServiceInvoiceRecId": None, "PurchRecId": None,
                "PaymentMethod": "PIX", "PaymentDate": d,
                "PaidFlag": "1", "ReceivedFlag": "0",
                "Description": f"Receita Federal - {tax_type}",
                "LedgerCategory": "TAX",
                "AmountCurDebit": row["amount"], "AmountCurCredit": 0,
                "PeriodMonth": d[:7], "Status": "P", "IsActive": "1",
            })
        jrn_desc = "Extrato Dez/2025 - Impostos Receita Federal (Reconciliação)"
        ok = ensure_journal(session, "/api/journals/tax", jrn_desc, "2025-12-12", tax_lines)
        if ok:
            print(f"  IMPORTED_TAX_JOURNAL  '{jrn_desc}'  ({len(tax_lines)} lines)")
            total_created += len(tax_lines)

    # Missing Dec Gustavo
    if gustavo_dec_missing:
        prolabore = 2760.00
        retirada = round(max(gustavo_dec_total - prolabore, 0), 2)
        month_end = "2025-12-30"
        payment_lines = [
            {
                "TransDate": month_end, "DueDate": month_end,
                "Voucher": "GUS-PROLABORE-2025-12",
                "BankAccountId": int(bank["RecId"]),
                "PaymentMethod": "PIX", "PaymentDate": month_end,
                "PaidFlag": "1", "ReceivedFlag": "0",
                "Description": "Pro-labore Gustavo - Dez/2025",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": prolabore, "AmountCurCredit": 0,
                "PeriodMonth": "2025-12", "Status": "P", "IsActive": "1",
            },
        ]
        if retirada > 0:
            payment_lines.append({
                "TransDate": month_end, "DueDate": month_end,
                "Voucher": "GUS-LUCRO-2025-12",
                "BankAccountId": int(bank["RecId"]),
                "PaymentMethod": "PIX", "PaymentDate": month_end,
                "PaidFlag": "1", "ReceivedFlag": "0",
                "Description": "Retirada de lucro Gustavo - Dez/2025",
                "LedgerCategory": "OPERATING",
                "AmountCurDebit": retirada, "AmountCurCredit": 0,
                "PeriodMonth": "2025-12", "Status": "P", "IsActive": "1",
            })
        jrn_desc = "Extrato Dez/2025 - Pix Gustavo (Reconciliação)"
        ok = ensure_journal(session, "/api/journals/payment", jrn_desc, month_end, payment_lines)
        if ok:
            print(f"  IMPORTED_GUSTAVO_JOURNAL  '{jrn_desc}'  ({len(payment_lines)} lines)")
            total_created += len(payment_lines)

    print(f"\n=== RECONCILIATION COMPLETE ===")
    print(f"Total entries imported: {total_created}")
    if total_created == 0:
        print("Database is fully up-to-date with bank statements.")


if __name__ == "__main__":
    main()
