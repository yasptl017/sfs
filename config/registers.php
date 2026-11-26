<?php

/**
 * Generic register browser definitions (read-only lists with search, filters and a full-field detail page).
 *   table   : source table (always filtered to the signed-in financial year)
 *   scope   : 'range'      -> rows of the user's range (division sees its ranges + itself, admin all)
 *             'range_or_all' -> as 'range', plus rows that apply to all ranges (range_id NULL)
 *             'division'   -> rows of the user's division (admin sees all)
 *             'all'        -> every row, any role
 *   roles   : which roles may open it
 *   columns : [label, column or alias, format] ; format = text | money | num | date ; column '_range' = office name
 *   select  : extra raw select expressions (aliases usable in columns / sum)
 *   sum     : aliases/columns to total in the header cards
 */
return [
    'parties' => [
        'title' => 'Party registration', 'table' => 'party_registrations', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Office', '_range'], ['Party code', 'party_code'], ['Round', 'round'], ['Party name', 'party_name'], ['Type', 'small_description'], ['PAN', 'pan_card_no'], ['GST', 'gst_no'], ['Bank', 'bank_name'], ['Account', 'account_no'], ['Approved %', 'approved_percent', 'num'], ['TDS %', 'tds', 'num'], ['Status', 'party_status']],
        'search' => ['party_name', 'party_code', 'pan_card_no', 'gst_no', 'account_no', 'small_description', 'party_mobile_no'], 'filters' => ['party_status' => 'Status', 'small_description' => 'Type'], 'order' => ['range_id', 'serial_number'],
    ],
    'tender-parties' => [
        'title' => 'Tender parties', 'table' => 'division_tender_parties', 'scope' => 'division', 'roles' => ['division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Range', 'range_name'], ['Type', 'tender_type'], ['Tender no.', 'tender_no'], ['Party code', 'party_code'], ['Party name', 'party_name'], ['Opening date', 'tender_opening_date', 'date'], ['Validity up to', 'tender_validity_up_to_date', 'date'], ['Fee', 'tender_fee_amount', 'money'], ['EMD', 'emd_amount', 'money'], ['Status', 'party_status']],
        'search' => ['party_name', 'party_code', 'tender_no', 'tender_code', 'pan_card_no', 'gst_no'], 'filters' => ['tender_type' => 'Type', 'party_status' => 'Status'], 'order' => ['serial_number'], 'sum' => ['tender_fee_amount', 'emd_amount'],
    ],
    'sf-beneficiaries' => [
        'title' => 'SF beneficiaries', 'table' => 'sf_beneficiaries', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Office', '_range'], ['Code', 'sf_bene_code'], ['Name', 'full_name'], ['Yojana', 'yojana_name'], ['Round', 'round'], ['Village', 'village'], ['Mobile', 'mobile_no'], ['Category', 'category'], ['Year', 'selection_year'], ['Plants', 'hactor_dcp_plants'], ['Status', 'party_status']],
        'search' => ['full_name', 'sf_bene_code', 'village', 'mobile_no', 'id_no', 'account_no', 'yojana_name'], 'filters' => ['yojana_name' => 'Yojana', 'selection_year' => 'Selection year', 'party_status' => 'Status'], 'order' => ['range_id', 'serial_number'],
    ],
    'wl-beneficiaries' => [
        'title' => 'WL beneficiaries', 'table' => 'wl_beneficiaries', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Office', '_range'], ['Code', 'wl_bene_code'], ['Name', 'full_name'], ['Yojana', 'yojana_name'], ['Village', 'village'], ['Animal', 'name_of_wild_animal_causing_incident'], ['Incident date', 'date_of_incident', 'date'], ['Compensation', 'total_amount_of_compensation_payable_for_incident', 'money'], ['Year', 'selection_year']],
        'search' => ['full_name', 'wl_bene_code', 'village', 'mobile_no', 'id_no', 'description'], 'filters' => ['yojana_name' => 'Yojana', 'selection_year' => 'Selection year'], 'order' => ['range_id', 'serial_number'], 'sum' => ['total_amount_of_compensation_payable_for_incident'],
    ],
    'd-wagers' => [
        'title' => 'D.Wager register', 'table' => 'd_wagers', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Office', '_range'], ['Code', 'd_wager_code'], ['Name', 'd_wager_name'], ['Years of service', 'years_of_service'], ['PAN', 'pan_card_no'], ['Account', 'account_no'], ['Bank', 'bank_name'], ['IFSC', 'ifsc'], ['CPF no.', 'cpf_no'], ['GPF %', 'gpf_percent', 'num'], ['CPF %', 'cpf_percent', 'num']],
        'search' => ['d_wager_name', 'd_wager_code', 'pan_card_no', 'account_no', 'cpf_no', 'gpf_no'], 'filters' => [], 'order' => ['range_id', 'serial_number'],
    ],
    'sor' => [
        'title' => 'SOR (schedule of rates)', 'table' => 'sor_items', 'scope' => 'range_or_all', 'roles' => ['range', 'division', 'admin'], 'group' => 'Rate lists',
        'columns' => [['Tender code', 'tender_code'], ['Range', 'range_name'], ['SOR code', 'divi_code'], ['Sr.', 'serial_number', 'num'], ['Particular', 'particular'], ['Unit', 'unit'], ['Latest rate', 'latest_rate', 'money'], ['In force from', 'latest_rate_from', 'date']],
        'select' => ['(SELECT r.rate FROM sor_rates r WHERE r.sor_item_id=t.id ORDER BY r.effective_from DESC LIMIT 1) AS latest_rate', '(SELECT r.effective_from FROM sor_rates r WHERE r.sor_item_id=t.id ORDER BY r.effective_from DESC LIMIT 1) AS latest_rate_from'],
        'search' => ['particular', 'divi_code', 'tender_code'], 'filters' => ['unit' => 'Unit', 'tender_code' => 'Tender code'], 'order' => ['id'],
        'children' => ['title' => 'Rates by effective date', 'table' => 'sor_rates', 'fk' => 'sor_item_id', 'columns' => [['Effective from', 'effective_from', 'date'], ['Rate', 'rate', 'money']], 'order' => 'effective_from'],
    ],
    'tender-rates' => [
        'title' => 'Tender rates', 'table' => 'tender_items', 'scope' => 'range_or_all', 'roles' => ['range', 'division', 'admin'], 'group' => 'Rate lists',
        'columns' => [['Tender code', 'tender_code'], ['Range', 'range_name'], ['Code', 'divi_code'], ['Sr.', 'serial_number', 'num'], ['Particular', 'particular'], ['Unit', 'unit'], ['Rate', 'rate', 'money'], ['Approval', 'approval_ref']],
        'search' => ['particular', 'divi_code', 'tender_code', 'approval_ref'], 'filters' => ['tender_code' => 'Tender code', 'unit' => 'Unit'], 'order' => ['id'],
    ],
    'estimates' => [
        'title' => 'Estimates', 'table' => 'estimate_items', 'scope' => 'range_or_all', 'roles' => ['range', 'division', 'admin'], 'group' => 'Rate lists',
        'columns' => [['Tender code', 'tender_code'], ['Range', 'range_name'], ['Code', 'divi_code'], ['Sr.', 'serial_number', 'num'], ['Place & item', 'place_item_name'], ['Unit', 'unit'], ['Rate', 'rate', 'money'], ['Quantity', 'quantity', 'num'], ['Amount', 'amount', 'money']],
        'search' => ['place_item_name', 'particular', 'divi_code', 'tender_code', 'remarks'], 'filters' => ['tender_code' => 'Tender code'], 'order' => ['id'], 'sum' => ['amount'],
    ],
    'bhav-patrak' => [
        'title' => 'Bhav Patrak', 'table' => 'bhav_patrak_items', 'scope' => 'range_or_all', 'roles' => ['range', 'division', 'admin'], 'group' => 'Rate lists',
        'columns' => [['Code', 'tender_code'], ['Range', 'range_name'], ['Item code', 'divi_code'], ['Sr.', 'serial_number', 'num'], ['Particular', 'particular'], ['Unit', 'unit'], ['Rate', 'rate', 'money'], ['Approval', 'approval_ref']],
        'search' => ['particular', 'divi_code', 'tender_code', 'approval_ref'], 'filters' => ['tender_code' => 'Code'], 'order' => ['id'],
    ],
    'wl-tender' => [
        'title' => 'WL tender / MOU', 'table' => 'wl_tender_items', 'scope' => 'range_or_all', 'roles' => ['range', 'division', 'admin'], 'group' => 'Rate lists',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Yojana', 'yojana_name'], ['Agreement', 'agreement_type'], ['Particular', 'particular'], ['Unit', 'unit'], ['Rate', 'rate', 'money'], ['Quantity', 'quantity', 'num'], ['Amount', 'amount', 'money']],
        'search' => ['particular', 'yojana_name'], 'filters' => [], 'order' => ['id'], 'sum' => ['amount'],
    ],
    'budget-position' => [
        'title' => 'Budget position (allotments)', 'table' => 'budget_codes', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Budget & allotments', 'detail' => false,
        'columns' => [['Code', 'budget_code'], ['Scheme', 'scheme'], ['Item', 'item'], ['Model', 'model'], ['From Circle', 'circle', 'money'], ['Adj. in', 'adj_in', 'money'], ['Adj. out', 'adj_out', 'money'], ['Adjusted', 'adjusted', 'money'], ['To ranges', 'distributed', 'money'], ['Pending', 'pending', 'money']],
        'select' => [
            '(SELECT COALESCE((SELECT f.amount FROM allotments_from_circle f WHERE f.budget_code_id=t.id ORDER BY f.source_time_stamp DESC, f.id DESC LIMIT 1),0)) AS circle',
            '(SELECT COALESCE(SUM(a.amount),0) FROM allotment_adjustments a WHERE a.to_budget_code_id=t.id) + (SELECT COALESCE(SUM(n.amount),0) FROM allotment_net_adjustments n WHERE n.budget_code_id=t.id AND n.amount>0) AS adj_in',
            '(SELECT COALESCE(SUM(a.amount),0) FROM allotment_adjustments a WHERE a.from_budget_code_id=t.id) + (SELECT COALESCE(-SUM(n.amount),0) FROM allotment_net_adjustments n WHERE n.budget_code_id=t.id AND n.amount<0) AS adj_out',
            '(SELECT COALESCE(SUM(r.amount),0) FROM allotments_to_range r WHERE r.budget_code_id=t.id) AS distributed',
        ],
        'computed' => ['adjusted' => 'circle + adj_in - adj_out', 'pending' => 'circle + adj_in - adj_out - distributed'],
        'search' => ['budget_code', 'scheme', 'item', 'model'], 'filters' => ['scheme' => 'Scheme', 'object_class' => 'Class'], 'order' => ['budget_code'], 'sum' => ['circle', 'adjusted', 'distributed', 'pending'],
    ],
    'allotments-to-range' => [
        'title' => 'Allotments to ranges (ledger)', 'table' => 'allotments_to_range', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Budget & allotments',
        'columns' => [['Ledger Sr.', 'ledger_sr', 'num'], ['Range', '_range'], ['Budget code', 'budget_code'], ['Target', 'target', 'num'], ['Amount', 'amount', 'money'], ['Remark', 'remark'], ['Entered', 'source_time_stamp', 'date'], ['By', 'entered_by']],
        'select' => ['(SELECT b.budget_code FROM budget_codes b WHERE b.id=t.budget_code_id) AS budget_code'],
        'search' => ['remark', 'entered_by'], 'search_sub' => ['(SELECT b.budget_code FROM budget_codes b WHERE b.id=t.budget_code_id)'], 'filters' => [], 'order' => ['source_time_stamp', 'id'], 'sum' => ['amount'],
    ],
    'district-allotments' => [
        'title' => 'District-wise allotments', 'table' => 'district_allotments', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Budget & allotments',
        'columns' => [['Budget code', 'budget_code'], ['Scheme', 'scheme'], ['Model', 'model'], ['District', 'district'], ['Entry date', 'entry_date', 'date'], ['Amount', 'amount', 'money']],
        'search' => ['budget_code', 'scheme', 'model'], 'filters' => ['district' => 'District', 'scheme' => 'Scheme'], 'order' => ['id'], 'sum' => ['amount'],
    ],
    'cash-accounts' => [
        'title' => 'Cash account (all offices)', 'table' => 'cash_accounts', 'scope' => 'division', 'roles' => ['division', 'admin'], 'group' => 'Budget & allotments',
        'columns' => [['Office', 'range_name'], ['Sr.', 'serial_number', 'num'], ['Month', 'cash_account_month'], ['Designation', 'designation'], ['Round/range', 'round_range'], ['Recovery type', 'recovery_type'], ['Recovery date', 'recovery_date', 'date'], ['Deposit date', 'bank_deposit_date', 'date'], ['Challan', 'challan_no'], ['Amount', 'amount', 'money']],
        'search' => ['details_of_recovery', 'recovery_type', 'challan_no', 'recovery_officer_name'], 'filters' => ['range_name' => 'Office', 'cash_account_month' => 'Month', 'recovery_type' => 'Recovery type'], 'order' => ['range_name', 'serial_number'], 'sum' => ['amount'],
    ],
    'd-wager-salaries' => [
        'title' => 'D.Wager salary register (monthly calculation)', 'table' => 'd_wager_salaries', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Office', '_range'], ['Sr.', 'serial_number', 'num'], ['Code', 'd_wager_code'], ['Name', 'd_wager_name'], ['Pay scale', 'pay_scale'], ['Year', 'salary_year'], ['Month', 'salary_month'], ['Working days', 'working_days', 'num'], ['Leaves', 'no_of_leaves', 'num'], ['Basic pay', 'basic_pay', 'money'], ['Total', 'total', 'money'], ['Deductions', 'total_deduction', 'money'], ['Net', 'net_amount', 'money']],
        'search' => ['d_wager_name', 'd_wager_code', 'pay_scale', 'salary_month'], 'filters' => ['salary_month' => 'Month', 'salary_year' => 'Year'], 'order' => ['range_id', 'id'], 'sum' => ['total', 'total_deduction', 'net_amount'],
    ],
    'd-wager-arrears' => [
        'title' => 'D.Wager arrears register', 'table' => 'd_wager_arrears', 'scope' => 'range', 'roles' => ['range', 'division', 'admin'], 'group' => 'Parties & beneficiaries',
        'columns' => [['Office', '_range'], ['Sr.', 'serial_number', 'num'], ['Code', 'd_wager_code'], ['Name', 'd_wager_name'], ['Pay year', 'pay_year'], ['Pay month', 'pay_month'], ['From', 'from_date', 'date'], ['To', 'to_date'], ['Payable (revised)', 'total_1', 'money'], ['Paid earlier', 'total_2', 'money'], ['Difference', 'total', 'money'], ['Deductions', 'total_deduction', 'money'], ['Net', 'net_amount', 'money']],
        'search' => ['d_wager_name', 'd_wager_code', 'arrears_description', 'pay_month'], 'filters' => ['pay_month' => 'Month', 'pay_year' => 'Year'], 'order' => ['range_id', 'id'], 'sum' => ['total', 'total_deduction', 'net_amount'],
    ],
    'deduction-cheques' => [
        'title' => 'Deduction cheques (per advice)', 'table' => 'deduction_cheques', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Other registers',
        'columns' => [['Advice month', 'advice_month'], ['Advice no.', 'advice_no', 'num'], ['Bank / details', 'details_of_cheque'], ['Cheque date', 'cheque_date', 'date'], ['Cheque no.', 'cheque_no'], ['Payee', 'payee'], ['Amount', 'amount', 'money'], ['Entered', 'source_time_stamp', 'date']],
        'search' => ['payee', 'cheque_no', 'details_of_cheque'], 'filters' => ['advice_month' => 'Advice month', 'details_of_cheque' => 'Bank'], 'order' => ['advice_no', 'id'], 'sum' => ['amount'],
    ],
    'party-limits' => [
        'title' => 'Party additional limits', 'table' => 'party_additional_limits', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Other registers',
        'columns' => [['Sr.', 'serial_number', 'num'], ['Party code', 'party_code'], ['Party name', 'party_name'], ['System limit', 'system_limit', 'money'], ['Additional', 'additional_limit', 'money'], ['Final', 'final_limit', 'money'], ['Party (no GST)', 'party_name_2'], ['Upper limit', 'party_upper_limit', 'money'], ['Additional (2)', 'party_additional_limit', 'money']],
        'search' => ['party_name', 'party_code', 'party_name_2'], 'filters' => [], 'order' => ['id'],
    ],
    'cancelled-cheques' => [
        'title' => 'Cancelled cheques', 'table' => 'cancelled_cheques', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Other registers',
        'columns' => [['Month', 'month'], ['Type', 'type'], ['Cheque no.', 'cheque_no'], ['Cheque date', 'cheque_date', 'date'], ['Advice no.', 'advice_no'], ['Amount', 'amount', 'money'], ['Party', 'party_name'], ['New cheque', 'new_cheque_no']],
        'search' => ['party_name', 'cheque_no', 'details'], 'filters' => [], 'order' => ['id'], 'sum' => ['amount'],
    ],
    'lookups' => [
        'title' => 'Lists & office settings', 'table' => 'lookups', 'scope' => 'all', 'roles' => ['division', 'admin'], 'group' => 'Other registers',
        'columns' => [['List', 'list_name'], ['Value', 'value'], ['Sheet row', 'source_row', 'num']],
        'search' => ['value', 'list_name'], 'filters' => ['list_name' => 'List'], 'order' => ['list_name', 'source_row'],
    ],
];
