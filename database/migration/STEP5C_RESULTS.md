# Step 5c - allotments, L.C., cash account, office config (validated)

Loader: `database/tools/load_5c.py`; migration `2026_11_24_000030_step5c_allotments_lc_cash.php`
(replaces the never-run migrations 000017-000020 and the old JSON `cash_accounts`, which were deleted).

| Table | Rows | Source |
|---|---:|---|
| allotments_to_range | 3,343 | History00 > Raw Data of Allotment (cols A-I) |
| allotments_from_circle | 311 | same sheet (cols R-Z) |
| allotment_adjustments | 109 | same sheet (cols AK-AR) |
| lc_entries | 113 (25-26) + 94 (26-27) | L.C. Entry |
| cash_accounts | 235 | Master00 > Credit data (all ranges) |
| lookups (office config) | 184 cells | Master00 > DropDown (stamps, remitter a/c, SNA codes) |
| budget_codes (enriched) | 282 | payment_mode, wing, major_head, demand_no taken from the ledger |

## Meaning of the ledgers (proven, not assumed)
- **Allotment from Circle = LATEST row per budget code** (rows are cumulative restatements). 282/282 codes match the sheet; total 685,194,758.
  The sum of all 311 rows (830,794,625) is NOT a meaningful total.
- **Adjusted allotment = latest circle + adjustments in - adjustments out.** 282/282 match.
- **Allotment to range = SUM of rows** (increments, some negative). 3,948/3,948 code x range cells match; total 460,220,577.
- `ledger_sr` (the sheet's own counter) is not unique (24 repeated values) - rows are keyed by source_row.

## Covered / not separately loaded
- Range Allotment History = raw to-range ledger + 188 subtotal rows (Grand Total 460,220,577 = ledger sum). Circle Allotment History and
  Adjustment History views are empty shells (counters only). Hidden `Budget Allotment` copies are formulas over the same ledgers.
- Division workbook `Cash Account` (44/44) and `DropDown` are duplicates of Master00 `Credit data` and Master 26-27 lists.

## Things for the user to review
- L.C. 25-26 Sr 53 (Divisional, Class-2, 1,20,000): dated 2026-10-13 but entered 2025-10-15 - probably a year typo. Kept as typed.
- 14 cash_accounts rows have no range: empty placeholder rows (month only, amount 0/blank).
- L.C. `Count` cell (col A, first data row) and "Unlocked" markers are derived/UI cells, not stored (kept in staging).
