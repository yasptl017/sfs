# Step 4 - Sheet classification (all 123 sheets)

Source codes: DIV = Division SFS 26-27 RFS (6 Nov 2026), H00 = History00 26-27 (15 Aug 2026), M25 = Master Data 25-26, M26 = Master Data 26-27, M00 = Master00 26-27 (23 Nov 2026).

| Class | Sheets | Meaning |
|---|---|---|
| MIGRATE | 38 | |
| MIGRATE (older/other version → audit or year-tagged) | 2 | |
| DUPLICATE (covered) | 4 | |
| VERIFY then skip/migrate | 11 | |
| REPORT (derived) | 37 | |
| CONFIG / lookup | 17 | |
| EMPTY | 14 | |

Nothing in classes REPORT / DUPLICATE / VERIFY is skipped until it has been proven covered by SQL at mapping time. Every sheet remains in `staging_rows` regardless.

| Wb | # | Sheet | State | Rows | Class | Why |
|---|---|---|---|---|---|---|
| DIV | 1 | DashBoard | vis | 12 | REPORT | navigation |
| DIV | 2 | 1 | vis | 295 | VERIFY | Division voucher entry grid; prove its 294 vouchers equal Master All Division rows |
| DIV | 3 | RNG Budget | vis | 58 | REPORT | view/report |
| DIV | 4 | Voucher Report | vis | 2 | REPORT | view/report |
| DIV | 5 | Detail45 | vis | 2 | VERIFY | Division voucher entry grid; prove its 294 vouchers equal Master All Division rows |
| DIV | 6 | Query | vis | 99 | REPORT | view/report |
| DIV | 7 | Party Reg. by Range | vis | 50 | DUPLICATE | subset of Master 26-27 (proven for SOR/Party/PartyCode) |
| DIV | 8 | D.Wager Registration | vis | 2 | EMPTY | header only / no rows |
| DIV | 9 | PartyCode | vis | 31 | DUPLICATE | subset of Master 26-27 (proven for SOR/Party/PartyCode) |
| DIV | 10 | Tender | vis | 401 | DUPLICATE | subset of Master 26-27 (proven for SOR/Party/PartyCode) |
| DIV | 11 | Bhav Patrak | vis | 2 | EMPTY | header only / no rows |
| DIV | 12 | SOR | vis | 399 | DUPLICATE | subset of Master 26-27 (proven for SOR/Party/PartyCode) |
| DIV | 13 | Estimate | vis | 2 | EMPTY | header only / no rows |
| DIV | 14 | Monthwise Expenditure | vis | 56 | REPORT | view/report |
| DIV | 15 | Party wise Payment | vis | 49 | REPORT | view/report |
| DIV | 16 | SF Beneficiary | vis | 2 | EMPTY | header only / no rows |
| DIV | 17 | SF Bene. Report | vis | 2 | REPORT | view/report |
| DIV | 18 | WL Tender or MOU | vis | 1 | EMPTY | header only / no rows |
| DIV | 19 | WL Beneficiary | vis | 2 | EMPTY | header only / no rows |
| DIV | 20 | WL Bene. Report | vis | 2 | REPORT | view/report |
| DIV | 21 | Dead stock Data | vis | 4 | REPORT | view/report |
| DIV | 22 | Car History | vis | 14 | REPORT | view/report |
| DIV | 23 | D.Wager Salary | vis | 25 | MIGRATE | Division D.Wager salary entries |
| DIV | 24 | D.Wager Arrears | vis | 6 | MIGRATE | Division D.Wager arrears entries |
| DIV | 25 | Cash Account | vis | 45 | MIGRATE | Division cash account (recoveries) |
| DIV | 26 | Cash Account Print | vis | 7 | REPORT | view/report |
| DIV | 27 | Sheet251 | vis | 0 | EMPTY | header only / no rows |
| DIV | 28 | Workwise History | vis | 2 | REPORT | view/report |
| DIV | 29 | Itemwise Query | vis | 235 | REPORT | view/report |
| DIV | 30 | Itemwise | vis | 745 | MIGRATE | Division vouchers at line level, 6 Nov 2026 (newer than History00, older than Master All) |
| DIV | 31 | Data Validations | hid | 635 | CONFIG | validation lists / column map / office registry |
| DIV | 32 | DropDown | hid | 64 | CONFIG | dropdown lists (location/banks/vehicles) |
| DIV | 33 | Budget Allotment DIVI | hid | 500 | VERIFY | formula copy of budget allotment; prove covered by Budget Allotment |
| DIV | 34 | Used Row | hid | 0 | EMPTY | header only / no rows |
| DIV | 35 | Col Header | hid | 25 | CONFIG | validation lists / column map / office registry |
| DIV | 36 | RNG Budget Data | hid | 107 | VERIFY | formula copy of budget allotment; prove covered by Budget Allotment |
| DIV | 37 | IMP DATA | hid | 67 | CONFIG | validation lists / column map / office registry |
| H00 | 1 | DashBoard | vis | 18 | REPORT | navigation/report |
| H00 | 2 | Circle Allotment History | vis | 427 | MIGRATE | circle allotment history |
| H00 | 3 | Range Allotment History | vis | 5624 | MIGRATE | range allotment history |
| H00 | 4 | Adjustment History | vis | 3 | MIGRATE | budget code adjustments |
| H00 | 5 | SF Bene. Report | vis | 214 | REPORT | navigation/report |
| H00 | 6 | WL Bene. Report | vis | 5 | REPORT | navigation/report |
| H00 | 7 | Workwise History | vis | 2 | REPORT | navigation/report |
| H00 | 8 | Itemwise Query | vis | 19 | REPORT | navigation/report |
| H00 | 9 | Itemwise | vis | 7318 | MIGRATE | all vouchers at line level, 15 Aug 2026 (7 vouchers exist ONLY here) |
| H00 | 10 | Data Validations | hid | 1770 | CONFIG | validation lists / column map / registry |
| H00 | 11 | Col Header | hid | 31 | CONFIG | validation lists / column map / registry |
| H00 | 12 | Raw Data of Allotment | hid | 3345 | VERIFY | hidden raw allotment log; compare with Range Allotment History |
| H00 | 13 | BlankRow | hid | 0 | EMPTY | empty |
| H00 | 14 | Budget Allotment | hid | 284 | VERIFY | formula copy of budget allotment |
| H00 | 15 | IMP DATA | hid | 69 | CONFIG | validation lists / column map / registry |
| M25 | 1 | DashBoard | vis | 11 | REPORT | navigation |
| M25 | 2 | Budget Allotment | vis | 272 | MIGRATE | budget code master + targets for the year (allotment totals are formulas) |
| M25 | 3 | SOR | vis | 164 | MIGRATE | rate list for the year |
| M25 | 4 | Bhav Patrak | vis | 80 | MIGRATE | rate list for the year |
| M25 | 5 | Estimate | vis | 3692 | MIGRATE | rate list for the year |
| M25 | 6 | WL Tender or MOU | vis | 1 | MIGRATE | rate list for the year |
| M25 | 7 | PartyCode | vis | 108 | MIGRATE | parties |
| M25 | 8 | Party Registration By Range | vis | 395 | MIGRATE | parties |
| M25 | 9 | DropDown | vis | 3199 | MIGRATE | range/round/beat/place + bank lists |
| M25 | 10 | Tender | vis | 340 | MIGRATE | rate list for the year |
| M25 | 11 | L.C. | vis | 38 | REPORT | L.C. grid = formulas over L.C. Entry |
| M25 | 12 | D.Wager Registration | vis | 269 | MIGRATE | D.Wager register |
| M25 | 13 | SF Beneficiary | vis | 2 | EMPTY | only #REF! |
| M25 | 14 | L.C. Entry | vis | 114 | MIGRATE | L.C. entries |
| M25 | 15 | WL Beneficiary | vis | 4 | MIGRATE | WL beneficiaries |
| M25 | 16 | Party Additional Limit | vis | 2 | MIGRATE | small registers |
| M25 | 17 | Cancelled Cheques | vis | 2 | MIGRATE | small registers |
| M25 | 18 | Col Header | hid | 22 | CONFIG | validation lists / column map / registry |
| M25 | 19 | Data Validations | hid | 504 | CONFIG | validation lists / column map / registry |
| M25 | 20 | BlankRow | hid | 0 | EMPTY | empty |
| M25 | 21 | IMP DATA | hid | 107 | CONFIG | validation lists / column map / registry |
| M26 | 1 | DashBoard | vis | 13 | REPORT | navigation |
| M26 | 2 | Budget Allotment | vis | 284 | MIGRATE | budget code master + targets for the year (allotment totals are formulas) |
| M26 | 3 | Bhav Patrak | vis | 1 | MIGRATE | rate list for the year |
| M26 | 4 | SOR | vis | 399 | MIGRATE | rate list for the year |
| M26 | 5 | WL Tender or MOU | vis | 3 | MIGRATE | rate list for the year |
| M26 | 6 | Estimate | vis | 1195 | MIGRATE | rate list for the year |
| M26 | 7 | Tender | vis | 401 | MIGRATE | rate list for the year |
| M26 | 8 | Sheet54 | vis | 0 | EMPTY | empty |
| M26 | 9 | PartyCode | vis | 199 | MIGRATE | parties |
| M26 | 10 | Party Registration By Range | vis | 372 | MIGRATE | parties |
| M26 | 11 | DropDown | vis | 3077 | MIGRATE | range/round/beat/place + bank lists |
| M26 | 12 | D.Wager Registration | vis | 277 | MIGRATE | D.Wager register |
| M26 | 13 | L.C. | vis | 35 | REPORT | L.C. grid = formulas over L.C. Entry |
| M26 | 14 | L.C. Entry | vis | 95 | MIGRATE | L.C. entries |
| M26 | 15 | SF Beneficiary | hid | 1990 | MIGRATE | SF beneficiaries (IMPORTRANGE cache, hidden) |
| M26 | 16 | WL Beneficiary | vis | 14 | MIGRATE | WL beneficiaries |
| M26 | 17 | Party Additional Limit | vis | 2 | MIGRATE | small registers |
| M26 | 18 | Cancelled Cheques | vis | 1 | MIGRATE | small registers |
| M26 | 19 | Col Header | hid | 22 | CONFIG | validation lists / column map / registry |
| M26 | 20 | Data Validations | hid | 634 | CONFIG | validation lists / column map / registry |
| M26 | 21 | BlankRow | hid | 0 | EMPTY | empty |
| M26 | 22 | IMP DATA | hid | 107 | CONFIG | validation lists / column map / registry |
| M00 | 1 | DashBoard | vis | 14 | REPORT | report/view |
| M00 | 2 | LC SHEET | vis | 35 | REPORT | report/view |
| M00 | 3 | Modelwise | vis | 21 | REPORT | report/view |
| M00 | 4 | Allotment Entry for Summary | vis | 394 | VERIFY | may contain manual monthly entries; inspect inputs before skipping |
| M00 | 5 | New Summary | vis | 568 | REPORT | report/view |
| M00 | 6 | Summary | vis | 286 | REPORT | report/view |
| M00 | 7 | New Summary Entry | vis | 456 | VERIFY | may contain manual monthly entries; inspect inputs before skipping |
| M00 | 8 | Summary Entry | vis | 4 | VERIFY | may contain manual monthly entries; inspect inputs before skipping |
| M00 | 9 | Unit Register | vis | 63 | REPORT | report/view |
| M00 | 10 | Query | vis | 996 | REPORT | report/view |
| M00 | 11 | Pending Vouchers | vis | 5 | REPORT | report/view |
| M00 | 12 | Plotwise | vis | 746 | REPORT | report/view |
| M00 | 13 | Passed Vouchers | vis | 6791 | VERIFY | derived from Master All treasury columns; prove every row exists there |
| M00 | 14 | Partywise | vis | 200 | REPORT | report/view |
| M00 | 15 | Advice+Range | vis | 82 | REPORT | report/view |
| M00 | 16 | Yearwise Plot Expe. | vis | 1396 | REPORT | report/view |
| M00 | 17 | Credit Data Report | vis | 52 | REPORT | report/view |
| M00 | 18 | Range+Month | vis | 78 | REPORT | report/view |
| M00 | 19 | Credit data | vis | 236 | MIGRATE | cash account / credit recoveries, all ranges |
| M00 | 20 | DropDown | vis | 44 | CONFIG | office stamps, remitter a/c, SNA codes |
| M00 | 21 | Ded. Cheques | vis | 1 | MIGRATE | cheque register |
| M00 | 22 | Master All | vis | 7566 | MIGRATE | PRIMARY voucher register: 7,565 vouchers (1 row each) + Item Details JSON lines |
| M00 | 23 | BlankRow | hid | 0 | EMPTY | empty |
| M00 | 24 | Budget Allotment | hid | 284 | VERIFY | formula copy of budget allotment |
| M00 | 25 | Col Header | hid | 28 | CONFIG | validation lists / column map / registry |
| M00 | 26 | Base Data | hid | 3311 | REPORT | derived data cube from Master All |
| M00 | 27 | IMP DATA | hid | 78 | CONFIG | validation lists / column map / registry |
| M00 | 28 | Data Validations | hid | 1024 | CONFIG | validation lists / column map / registry |
