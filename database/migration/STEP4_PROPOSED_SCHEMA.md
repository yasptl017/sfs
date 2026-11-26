# Step 4 - Proposed normalized schema (for approval, nothing created yet)

## Proven facts this design rests on
- `Master All` (Master00, 23 Nov 2026) = **one row per voucher**: 7,565 vouchers, no duplicate keys.
  Key = (Range, Entry Type, Sr. No.). Line items are in the `Item Details` column as JSON arrays of 10 positions:
  `[SOR Code, Work Description, Work Order No, Work Order Date, Work Start, Work End, No. of Unit, Unit, SOR Rate, Amount]`
  (15,764 lines; all parse). Verified against History00 line columns: 3,282 of 3,395 shared vouchers identical; the other 113 were edited after 15 Aug (newer value is live).
- History00 `Itemwise` (15 Aug): 3,402 vouchers / 7,317 lines. 3,395 are in Master All. **7 are only here** (Idar T-24; Khedbrahma T-117/118/122; Modasa T-74; Prantij T-139; Vadali Bene-11).
- Division workbook `Itemwise` (6 Nov): 294 Division vouchers, all in Master All.
- Precedence for conflicts (extends your earlier rule): Master All > Division WB > History00. Older versions kept in `voucher_versions`.
- Master 25-26 and 26-27 are genuinely different years (e.g. 2,706 estimate lines only in 25-26; zero overlap in L.C. entries) -> `financial_year_id` on every reference table.
- Division copies of SOR / Party Registration / PartyCode are subsets of Master 26-27.

## Tables
**Shared**
- `financial_years` (code `2025-26`, `2026-27`, ...). Vouchers belong to the workbook year (2026-27); `scheme_year` stays a separate column on the voucher.
- `users` (existing): 14 logins = 13 range logins (Bayad, Bhiloda, Dhansura, Himatnagar, Idar, Khedbrahma, Malpur, Meghraj, Modasa, Prantij, Talod, Vadali, Vijaynagar) + 1 Division login. Add `office_code`.
- `range_locations` (existing; Range/Round/Beat/Place/Ha/Place Year from `DropDown`) + `lookups` (banks, pay scales, vehicles, payment modes).
- `budget_codes` (existing, + `financial_year_id`): code, operating head, scheme, class, object code, description, item, model, scheme year, rate, payment mode, target.

**Vouchers (replaces the 6 JSON `*_entries` tables)**
- `vouchers`: id, financial_year_id, range_id, `entry_type` (tender, free, dwager_salary, dwager_arrears, sf_bene, wl_bene), serial_number, **unique(financial_year_id, range_id, entry_type, serial_number)**; tender_code, budget_code, scheme/class/model/scheme_year, entry_month, docket_no, round/beat/place/area, description, bill_no/date; party snapshot (code, name, PAN, GST, bank a/c, IFSC, branch, small description, approved %); totals (sub_total, SGST, CGST, IGST, labour cess, adjustment, payable_total) and deductions (SGST, CGST, IGST, GPF, CPF, P.Tax, group insurance, deposit, TDS, labour cess, other, total, net); division workflow (received_at_division, approved_by_acf, rejected_reason/date, remark); advice (advice_no/date, IFMS bill type, bill register no, order outward no, GST challan no); treasury (voucher no/date/month, ePayment code, UTR, status, return date/reason, transit reg sr no); payment (mode, EDP code, voucher_no_final); beneficiary snapshot (code, name, year/instalment, dates, Gujarati name, mobile, gender, address, category, district/taluka/village, survey no, ID, GPS, selection year, plants, survival %); audit (unique_code_of_data_entry, entered_by, time_stamp, times_edited, program_remark); extra1-4; `source_workbook`, `source_sheet`, `source_row`.
- `voucher_items`: voucher_id, line_no, sor_code, description, work_order_no/date, work_start/end, quantity, unit, rate, amount.
- `voucher_versions` (audit): voucher key, source workbook/sheet/row, full snapshot JSON, reason (`superseded`, `only_in_older_export`).

**Rates and registers (all with `financial_year_id`)**
- `sor_items` + `sor_rates(effective_from, rate)` (SOR has dated rate columns: 2024-04-01, 2025-05-15, 2026-07-01, 2026-08-01)
- `tender_items`, `estimate_items`, `bhav_patrak_items`, `wl_tender_items`
- `parties` (merge of `Party Registration By Range` + `PartyCode`; tender type/mode/no/opening date/validity), `party_additional_limits`
- `sf_beneficiaries`, `wl_beneficiaries` (+ WL columns: case numbers, animal, incident date, livestock, amounts)
- `d_wagers` (register), `d_wager_salaries`, `d_wager_arrears` (Division sheets)
- `allotments_from_circle`, `allotments_to_range`, `allotment_adjustments`, `lc_entries`
- `cash_accounts` (Division sheet + Master00 `Credit data`), `cancelled_cheques`, `cheque_deductions`

**Kept from staging**: `staging_sheets` / `staging_rows` stay as the permanent source-of-record. Every migrated row stores source workbook/sheet/row so any record can be traced back to its spreadsheet cell.

## GUI impact
Controllers and views for tender / free / d-wager / sf-bene / wl-bene entries switch from `data[...]` JSON to typed columns plus line-item rows; list pages gain financial-year, range and entry-type filters. Registration, allotment, L.C., cash-account pages keep their layout and move to the new columns. Reports (voucher print, abstract, cashbook, SOR limit, summary) are re-pointed to the new tables and checked against the spreadsheet totals.

## Validation plan per step
1. Row counts per source sheet == rows inserted + rows deliberately merged (listed).
2. Sum of money columns (payable total, net, deductions, amounts) per range and per entry type == source sums.
3. Every voucher key in any source exists in `vouchers`; every line in `Item Details` exists in `voucher_items`.
4. Spot-compare 50 random vouchers cell-by-cell vs staging.
5. GUI: open each migrated module as a range login and the Division login and compare counts and totals.
