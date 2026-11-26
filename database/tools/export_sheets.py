"""
Step 3 exporter: dump every non-empty row of every sheet of every workbook in
database/GoogleSheets to JSONL, plus a manifest with expected counts and a
SHA-256 per sheet. Nothing is filtered or interpreted here.

Run:  python -I -X utf8 database/tools/export_sheets.py
Output: storage/app/private/migration/staging/*.jsonl + manifest.json

Row line format:  {"r": <1-based sheet row>, "c": [<cell>, ...]}
Cells are typed JSON (number / string / bool / null). Dates become
"YYYY-MM-DD HH:MM:SS". Trailing empty cells are trimmed; interior empties stay
null so column positions are preserved. Cached values are exported (for Google
IMPORTRANGE sheets the cached value IS the data).
"""
import datetime as dt
import hashlib
import json
import os
import re
import sys

import openpyxl

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
SRC = os.path.join(ROOT, "database", "GoogleSheets")
OUT = os.path.join(ROOT, "storage", "app", "private", "migration", "staging")


def slug(s):
    return re.sub(r"[^A-Za-z0-9._-]+", "_", s)


def norm(v):
    if v is None:
        return None
    if isinstance(v, dt.datetime):
        return v.strftime("%Y-%m-%d %H:%M:%S")
    if isinstance(v, dt.date):
        return v.strftime("%Y-%m-%d")
    if isinstance(v, dt.time):
        return v.strftime("%H:%M:%S")
    if isinstance(v, dt.timedelta):
        return str(v)
    if isinstance(v, str) and v == "":
        return None
    return v


def file_sha(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def main():
    os.makedirs(OUT, exist_ok=True)
    manifest = []
    for fname in sorted(os.listdir(SRC)):
        if not fname.lower().endswith(".xlsx"):
            continue
        path = os.path.join(SRC, fname)
        print("exporting", fname, flush=True)
        sha = file_sha(path)
        wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
        for order, ws in enumerate(wb.worksheets, 1):
            out_name = f"{slug(fname[:-5])}__{order:02d}_{slug(ws.title)}.jsonl"
            h = hashlib.sha256()
            rows = cells = maxc = 0
            with open(os.path.join(OUT, out_name), "w", encoding="utf-8", newline="\n") as fh:
                for r, row in enumerate(ws.iter_rows(values_only=True), 1):
                    vals = [norm(v) for v in row]
                    while vals and vals[-1] is None:
                        vals.pop()
                    if not vals:
                        continue
                    n = sum(1 for v in vals if v is not None)
                    cj = json.dumps(vals, ensure_ascii=False, separators=(",", ":"))
                    h.update(f"{r}\t{cj}\n".encode("utf-8"))
                    fh.write(json.dumps({"r": r, "c": vals}, ensure_ascii=False, separators=(",", ":")) + "\n")
                    rows += 1
                    cells += n
                    maxc = max(maxc, len(vals))
            manifest.append({
                "source_file": fname, "source_sha256": sha, "sheet_order": order,
                "sheet_name": ws.title, "sheet_state": ws.sheet_state,
                "file": out_name, "rows": rows, "cells": cells, "max_col": maxc,
                "rows_sha256": h.hexdigest(),
            })
            print(f"  {order:02d} {ws.title!r:34} rows={rows:6} cells={cells:8}", flush=True)
    with open(os.path.join(OUT, "manifest.json"), "w", encoding="utf-8") as fh:
        json.dump(manifest, fh, ensure_ascii=False, indent=1)
    print("DONE sheets=%d rows=%d cells=%d" % (
        len(manifest), sum(m["rows"] for m in manifest), sum(m["cells"] for m in manifest)))


if __name__ == "__main__":
    sys.exit(main())
