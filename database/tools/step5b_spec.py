"""
Step 5b table specification (rate lists + registers). One spec drives BOTH the generated
Laravel migration and the loader, so schema and loader cannot drift apart.
"""
M25 = "Master Data SF Sabarkantha 25-26 DFS.xlsx"
M26 = "Master Data SF Sabarkantha 26-27 DFS.xlsx"
SRC = {"2025-26": M25, "2026-27": M26}

# header prefix (lower-case) -> db column name. Applied to every table (first match wins).
COMMON = [
    ("sr.", "serial_number"), ("sr", "serial_number"),
    ("pan card no", "pan_card_no"), ("gst no", "gst_no"), ("account no", "account_no"), ("bank name", "bank_name"),
    ("ifsc", "ifsc"), ("branch", "branch"), ("approved %", "approved_percent"),
    ("deduction sgst", "deduction_sgst"), ("deduction cgst", "deduction_cgst"), ("deduction igst", "deduction_igst"),
    ("deduction labour cess", "deduction_labour_cess"), ("tds %", "tds"), ("deposit deduction % from bill", "deposit_deduction"),
    ("party approval no", "party_approval_no"), ("party aadhaar no", "party_aadhaar_no"), ("party mobile no", "party_mobile_no"),
    ("party email", "party_email"), ("party address", "party_address"), ("small description", "small_description"),
    ("party name", "party_name"), ("party code", "party_code"), ("tender code", "tender_code"), ("round", "round"), ("beat", "beat"),
    ("link of doc", "link_of_doc"), ("party extra 1", "extra_1"), ("party extra 2", "extra_2"), ("party extra 3", "extra_3"),
    ("party extra 4", "extra_4"), ("party status", "party_status"), ("time stamp", "source_time_stamp"),
    ("entered by", "entered_by"), ("no. of times party edited", "times_edited"),
    ("mobile no", "mobile_no"), ("bene. full name", "full_name"), ("bene. name in gujarati", "name_gujarati"),
    ("name of yojana", "yojana_name"), ("fathers name", "father_name"), ("birth date", "birth_date"), ("pin code", "pin_code"),
    ("survey no", "survey_block_no"), ("id type", "id_type"), ("id no", "id_no"), ("gps-n", "gps_n"), ("gps-e", "gps_e"),
    ("bene. selection year", "selection_year"), ("district", "district"), ("taluka", "taluka"), ("village", "village"),
    ("category", "category"), ("gender", "gender"), ("address", "address"),
    ("divi. code", "divi_code"), ("particular", "particular"), ("unit", "unit"),
]
# exact lower-case header -> name (checked before prefixes)
EXACT = {"gps": "gps", "range": "RANGE", "rate": "rate", "sr.": "serial_number", "sr": "serial_number"}
FORCE_STR = {"hactor_dcp_plants", "unit", "serial_number_text"}  # never convert to numbers

# kinds the inferer may pick (keyword gated): money/percent/qty -> dec ; counts -> int ; dates -> date/datetime
NUM_KEYS = ("%", "amount", "rate", "cost", "share", "quantity", "limit", "total", "measurement", "ha.", "years of service", "no. of animals")
DATE_KEYS = ("date", "time stamp", "birth", "validity")

TABLES = [
    dict(table="party_registrations", owner="range", keep=["attachments"], status=True, key=[4],
         sheets={fy: (f, "Party Registration By Range") for fy, f in SRC.items()}),
    dict(table="division_tender_parties", owner="range", keep=["attachments"], status=True, key=[5],
         sheets={fy: (f, "PartyCode") for fy, f in SRC.items()}, force={"Tender No.": "str", "Tender Type": "str"}),
    dict(table="sf_beneficiaries", owner="range", keep=["attachments"], status=True, key=[5], names={"bene. code": "sf_bene_code"},
         sheets={"2026-27": (M26, "SF Beneficiary")}),
    dict(table="wl_beneficiaries", owner="range", keep=["attachments"], status=True, key=[5], names={"bene. code": "wl_bene_code", "remark for wl": "remark"},
         sheets={fy: (f, "WL Beneficiary") for fy, f in SRC.items()}),
    dict(table="d_wagers", owner="range", key=[1], names={"d.wager code": "d_wager_code", "d.wager name": "d_wager_name", "no. of year": "years_of_service", "cpf no": "cpf_no", "gpf no": "gpf_no", "gpf %": "gpf_percent", "cpf %": "cpf_percent"},
         sheets={fy: (f, "D.Wager Registration") for fy, f in SRC.items()}),
    dict(table="tender_items", owner="range", key=[0, 2], names={"tender approval": "approval_ref"},
         sheets={fy: (f, "Tender") for fy, f in SRC.items()}),
    dict(table="bhav_patrak_items", owner="range", key=[0, 2], names={"bhav patrak approval": "approval_ref"},
         sheets={fy: (f, "Bhav Patrak") for fy, f in SRC.items()}),
    dict(table="estimate_items", owner="range", key=[0, 2], names={"place & item name": "place_item_name", "rate approved": "rate_approval_ref", "remarks1": "remarks", "sr.": "serial_number", "quantity": "quantity"},
         sheets={fy: (f, "Estimate") for fy, f in SRC.items()}),
    dict(table="wl_tender_items", owner="range", key=[0, 4, 5, 6], names={"name of yojana": "yojana_name", "type of agreement": "agreement_type", "rate approved": "rate_approval_ref", "remarks1": "remarks"},
         sheets={fy: (f, "WL Tender or MOU") for fy, f in SRC.items()}),
    dict(table="party_additional_limits", owner=None, key=[1, 2, 10, 12, 4], names={},
         sheets={fy: (f, "Party Additional Limit") for fy, f in SRC.items()}),
    dict(table="cancelled_cheques", owner=None, key=[3, 5, 6], names={},
         sheets={fy: (f, "Cancelled Cheques") for fy, f in SRC.items()}),
]
