"""
Step 6 generator: voucher tables from the real Master All header (122 columns).
Writes database/migration/step6_spec.json and the Laravel migration.   python -I -X utf8 database/tools/gen_6.py
"""
import json
import os
import re

import pymysql

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
SRC = "Master00 SF Sabarkantha 26-27 DFS.xlsx"

MONEY = {"Sub Total", "Amount as per Approved Rate", "SGST", "CGST", "IGST", "Labour Cess", "Adjustment (+ or -)", "Payable Total Amount",
         "Deduction SGST", "Deduction CGST", "Deduction IGST", "GPF", "CPF", "P.Tax", "Group Insurance", "Deposit Deduction Amount from Bill",
         "TDS", "Deduction Labour Cess", "Other Deductions", "Total Deduction", "Net Amount", "Approved %", "Area (Ha.)", "Ha / DCP Plants",
         "Total No. of Plants", "GPS-N", "GPS-E", "Deduction of Ropa Amount for VruxKheti"}
INT = {"Sr. No.", "Docket No.", "Register Page No.", "Advice No.", "Year / Instal. No.", "No. of times Edited"}
DATE = {"Bill Date", "Rejection Date", "Advice Date", "Treasury Approval Date", "Bene. Application Date", "Bene. Application Sanctioned Date",
        "Birth Date", "Survival % Data Date", "Treasury Return Date"}
DATETIME = {"Time Stamp"}
NAMES = {"Range": "range", "Sr. No.": "serial_number", "Entry Type": "entry_type", "Area (Ha.)": "area_ha", "Adjustment (+ or -)": "adjustment",
         "P.Tax": "p_tax", "Join 1 to 9": "join_1_to_9", "Year / Instal. No.": "year_installment_no", "Survival %": "survival_percent",
         "Deposit Deduction Amount from Bill": "deposit_deduction_amount", "SF Model + Year": "sf_model_year", "Status": "status_log",
         "Time Stamp": "source_time_stamp", "Entered By": "entered_by", "No. of times Edited": "times_edited", "Item Details": "item_details_json",
         "Approved %": "approved_percent", "Ha / DCP Plants": "ha_dcp_plants", "Fathers Name in case of Female Bene.": "father_name",
         "Survey No. / Block No.": "survey_block_no", "Amount as per Approved Rate": "amount_as_per_approved_rate", "Remarks By RFO If Any": "remarks_by_rfo",
         "Unique Code of Data Entry": "unique_code", "Treasury Voucher No.": "treasury_voucher_no", "ePayment Code": "epayment_code", "UTR No.": "utr_no",
         "Reason of Return by Treasury": "treasury_return_reason", "Bill Transit Reg. Sr. No.": "bill_transit_reg_sr_no", "Bene. Name In Gujarati": "bene_name_gujarati",
         "Survival % Data Date": "survival_data_date", "Deduction of Ropa Amount for VruxKheti": "ropa_deduction_vruxkheti"}
TEXT_HINT = ("description", "remark", "details", "status", "reason", "join", "address", "model", "scheme", "place", "bene. name", "name of")


def env(path):
    out = {}
    for line in open(path, encoding="utf-8"):
        m = re.match(r"^([A-Z_]+)=(.*)$", line.strip())
        if m:
            out[m.group(1)] = m.group(2).strip('"')
    return out


E = env(os.path.join(ROOT, ".env"))
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4")
cur = db.cursor()
cur.execute("SELECT id FROM staging_sheets WHERE source_file=%s AND sheet_name='Master All'", (SRC,))
sid = cur.fetchone()[0]
cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
rows = [(r, json.loads(c)) for r, c in cur.fetchall()]
hdr = rows[0][1]
vals = {i: [] for i in range(len(hdr))}
for r, c in rows[1:]:
    for i, v in enumerate(c[:len(hdr)]):
        if v is not None:
            vals[i].append(v)


def slug(h):
    return re.sub(r"[^a-z0-9]+", "_", h.lower()).strip("_")[:55].rstrip("_") or "col"


cols, used = [], set()
for i, h in enumerate(hdr):
    h = str(h) if h is not None else None
    if h is None:
        continue
    if h == "None":
        h_name = "unnamed_col_%d" % i
    else:
        h_name = h
    name = NAMES.get(h, slug(h_name))
    if name == "range":
        cols.append(dict(idx=i, header=h, name="range", kind="range"))
        used.add("range")
        continue
    base, k = name, 2
    while name in used or name in ("id", "financial_year_id", "range_id", "range_name"):
        name = f"{base}_{k}"
        k += 1
    used.add(name)
    v = vals[i]
    if h in MONEY:
        kind = "dec"
        assert all(isinstance(x, (int, float)) and not isinstance(x, bool) for x in v), h
    elif h in INT:
        kind = "int"
        assert all(isinstance(x, (int, float)) and x == int(x) for x in v), h
    elif h in DATE:
        kind = "date"
        assert all(isinstance(x, str) and re.match(r"^\d{4}-\d{2}-\d{2}", x) for x in v), h
    elif h in DATETIME:
        kind = "datetime"
        assert all(isinstance(x, str) and re.match(r"^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$", x) for x in v), h
    else:
        ln = max([len(str(int(x)) if isinstance(x, float) and x == int(x) else str(x)) for x in v] or [0])
        kind = "text" if (ln > 255 or any(t in h.lower() for t in TEXT_HINT)) else "str"
        if name == "item_details_json":
            kind = "longtext"
    cols.append(dict(idx=i, header=h, name=name, kind=kind, n=len(v)))

spec = dict(source=SRC, sheet="Master All", columns=cols)
os.makedirs(os.path.join(ROOT, "database", "migration"), exist_ok=True)
json.dump(spec, open(os.path.join(ROOT, "database", "migration", "step6_spec.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)


def php(c):
    n, k = c["name"], c["kind"]
    return {"str": f"$t->string('{n}', 255)->nullable();", "text": f"$t->text('{n}')->nullable();", "longtext": f"$t->longText('{n}')->nullable();",
            "int": f"$t->bigInteger('{n}')->nullable();", "dec": f"$t->decimal('{n}', 20, 6)->nullable();", "date": f"$t->date('{n}')->nullable();",
            "datetime": f"$t->dateTime('{n}')->nullable();"}[k]


body = "\n".join("            " + php(c) for c in cols if c["kind"] != "range")
mig = """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

/**
 * Migration Step 6 - GENERATED by database/tools/gen_6.py from database/migration/step6_spec.json (Master All header).
 * vouchers: one row per voucher. voucher_items: its work/line items. voucher_versions: older copies kept for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['voucher_versions', 'voucher_items', 'vouchers'] as $old) {
            Schema::dropIfExists($old);
        }

        Schema::create('vouchers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('range_id')->constrained('users')->restrictOnDelete();
            $t->string('range_name', 100)->nullable();
""" + body + """
            $t->boolean('only_in_older_export')->default(false); // exists in an older export but not in the latest register
            $t->string('source_kind', 20)->default('master_all');
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->unique(['financial_year_id', 'range_id', 'entry_type', 'serial_number'], 'vouchers_key_unique');
            $t->index('budget_code');
            $t->index('advice_no');
            $t->index('party_code');
            $t->index('treasury_voucher_no');
            $t->index(['financial_year_id', 'entry_month']);
        });

        Schema::create('voucher_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $t->unsignedInteger('line_no');
            $t->string('sor_code')->nullable();
            $t->text('description')->nullable();
            $t->string('work_order_no', 500)->nullable();
            $t->date('work_order_date')->nullable();
            $t->date('work_start_date')->nullable();
            $t->date('work_end_date')->nullable();
            $t->decimal('quantity', 20, 6)->nullable();
            $t->string('unit')->nullable();
            $t->decimal('rate', 20, 6)->nullable();
            $t->decimal('amount', 20, 6)->nullable();
            $t->longText('raw_line'); // the original 10-field line exactly as in the source (JSON)
            $t->unique(['voucher_id', 'line_no']);
            $t->index('sor_code');
        });

        Schema::create('voucher_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('range_id')->constrained('users')->restrictOnDelete();
            $t->string('entry_type', 30);
            $t->unsignedInteger('serial_number');
            $t->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $t->string('version_kind', 30);        // history00_snapshot | division_workbook_snapshot
            $t->string('source_file');
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->json('source_rows');               // rows of the older export that make up this voucher
            $t->longText('snapshot');              // JSON: {fields: {header: value}, items: [...]}
            $t->boolean('differs_from_live')->default(false);
            $t->json('diff_fields')->nullable();   // names of the fields whose value differs from the live voucher
            $t->timestamps();
            $t->index(['financial_year_id', 'range_id', 'entry_type', 'serial_number'], 'voucher_versions_key');
        });
    }

    public function down(): void
    {
        foreach (['voucher_versions', 'voucher_items', 'vouchers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
"""
open(os.path.join(ROOT, "database", "migrations", "2026_11_24_000031_step6_vouchers.php"), "w", encoding="utf-8").write(mig)
ks = {}
for c in cols:
    ks[c["kind"]] = ks.get(c["kind"], 0) + 1
print("columns:", len(cols), ks)
print("names:", [c["name"] for c in cols][:60])
