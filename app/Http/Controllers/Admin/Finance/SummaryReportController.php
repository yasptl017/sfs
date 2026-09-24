<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BillAdvice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SummaryReportController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'
    ];

    protected array $paymentModes = [
        'ALL',
        'IFMS',
        'SNA'
    ];

    protected array $reportTypes = [
        'summary_abstract' => 'Abstract of Summary Report (ગ્રાન્ટ ગોશવારો)',
        'summary_merged_salary' => 'Summary Report (Merged with Salary / પગાર સાથે)',
        'summary_only_lc' => 'Summary Report (Only LC / માત્ર મજૂરી ખર્ચ)',
    ];

    private function authorizeDivision(Request $request): void
    {
        if ($request->user()?->role !== 'division') {
            abort(403, 'Unauthorized. Division access required.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $selectedMonth = $request->query('month', 'Jul');
        $selectedPaymentMode = $request->query('payment_mode', 'ALL');
        $selectedReportType = $request->query('report_type', 'summary_abstract');

        return view('admin.finance.summary-reports.index', [
            'months' => $this->months,
            'paymentModes' => $this->paymentModes,
            'reportTypes' => $this->reportTypes,
            'selectedMonth' => $selectedMonth,
            'selectedPaymentMode' => $selectedPaymentMode,
            'selectedReportType' => $selectedReportType,
            'divisionUser' => $request->user(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'month' => ['required', 'string', 'max:50'],
            'payment_mode' => ['nullable', 'string', 'in:ALL,IFMS,SNA'],
            'report_type' => ['required', 'string'],
        ]);

        $month = $validated['month'];
        $paymentMode = $validated['payment_mode'] ?? 'IFMS';
        $reportType = $validated['report_type'];

        $data = $this->compileSummaryData($request->user(), $month, $paymentMode, $reportType);

        return response()->json([
            'success' => true,
            'month' => $month,
            'payment_mode' => $paymentMode,
            'report_type' => $reportType,
            'data' => $data,
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeDivision($request);

        $month = $request->query('month', 'Jul');
        $paymentMode = $request->query('payment_mode', 'IFMS');
        $reportType = $request->query('report_type', 'summary_abstract');

        $data = $this->compileSummaryData($request->user(), $month, $paymentMode, $reportType);

        return view('admin.finance.summary-reports.print', [
            'month' => $month,
            'paymentMode' => $paymentMode,
            'reportType' => $reportType,
            'data' => $data,
            'divisionUser' => $request->user(),
        ]);
    }

    private function compileSummaryData(
        User $divisionUser,
        string $month,
        string $paymentMode,
        string $reportType
    ): array {
        $profile = $divisionUser->getOrCreateOfficeProfile();

        if ($reportType === 'summary_abstract') {
            return $this->compileAbstractSummaryData($profile, $month, $paymentMode);
        }

        return $this->compileDetailedSummaryData($profile, $month, $paymentMode, $reportType);
    }

    private function compileAbstractSummaryData(\App\Models\OfficeProfile $profile, string $month, string $paymentMode): array
    {
        $sections = [
            [
                'demand_key' => '2406/26',
                'demand_title' => '2406/26 Demand 26 (Revenue)',
                'schemes' => [
                    ['name' => '02- Divisional (2406/26)', 'sanctioned' => 2370000.00, 'released' => 608000.00, 'last_month' => 263471.00, 'current_month' => 118360.00],
                    ['name' => '02- Divisional (Charged) (2406/26)', 'sanctioned' => 1754912.00, 'released' => 1754912.00, 'last_month' => 0.00, 'current_month' => 0.00],
                    ['name' => '03- Building (2406/26)', 'sanctioned' => 1100000.00, 'released' => 402000.00, 'last_month' => 0.00, 'current_month' => 0.00],
                    ['name' => '15-Forest Research (NT) (2406/26)', 'sanctioned' => 361500.00, 'released' => 104000.00, 'last_month' => 0.00, 'current_month' => 0.00],
                    ['name' => '17- Gujarat Community Forestry Scheme NT (2406/26)', 'sanctioned' => 32920000.00, 'released' => 13690000.00, 'last_month' => 4178200.00, 'current_month' => 3282864.00],
                    ['name' => '2406-26 Implementation of Mahatma Gandhi National Rural Guarantee Act (2406/26)', 'sanctioned' => 240000.00, 'released' => 80000.00, 'last_month' => 0.00, 'current_month' => 19040.00],
                    ['name' => '2406/26 Mgmt and Development of Wildlife (NT) (2406/26)', 'sanctioned' => 1225000.00, 'released' => 367000.00, 'last_month' => 20000.00, 'current_month' => 0.00],
                ],
            ],
            [
                'demand_key' => '2406/95',
                'demand_title' => '2406/95 Demand 95 (SCSP)',
                'schemes' => [
                    ['name' => '05 Scheduled Castes Sub-Plan (SCSP) (2406/95)', 'sanctioned' => 2925800.00, 'released' => 483000.00, 'last_month' => 0.00, 'current_month' => 9863.00],
                ],
            ],
            [
                'demand_key' => '2406/96',
                'demand_title' => '2406/96 Demand 96 (Tribal)',
                'schemes' => [
                    ['name' => '17- FST- 9 Gujarat Community Forestry Project (Trible) (2406/96)', 'sanctioned' => 450000.00, 'released' => 112000.00, 'last_month' => 26420.00, 'current_month' => 12997.00],
                    ['name' => '35 Community Forestry Project (Trible) (2406/96)', 'sanctioned' => 24513000.00, 'released' => 5564000.00, 'last_month' => 2564302.00, 'current_month' => 790816.00],
                ],
            ],
            [
                'demand_key' => '4406/26',
                'demand_title' => '4406/26 Demand 26 (Capital)',
                'schemes' => [
                    ['name' => '10- FST- 8 Community Forestry Scheme (4406/26)', 'sanctioned' => 393194261.00, 'released' => 51100000.00, 'last_month' => 12704599.00, 'current_month' => 36037937.00],
                ],
            ],
            [
                'demand_key' => '4406/95',
                'demand_title' => '4406/95 Demand 95 (SCSP Capital)',
                'schemes' => [
                    ['name' => 'FST- 8 Scheduled Castes Sub Plan Scheme for Fruit Plantations (4406/95)', 'sanctioned' => 50404778.00, 'released' => 17112000.00, 'last_month' => 7251741.00, 'current_month' => 1400857.00],
                ],
            ],
            [
                'demand_key' => '4406/96',
                'demand_title' => '4406/96 Demand 96 (Tribal Capital)',
                'schemes' => [
                    ['name' => '06- FST- 8 Community Forestry Scheme (Trible) (4406/96)', 'sanctioned' => 116183725.00, 'released' => 32510000.00, 'last_month' => 7258181.00, 'current_month' => 17803696.00],
                ],
            ],
        ];

        $processedSections = [];
        $head2406Totals = ['sanctioned' => 0, 'released' => 0, 'last_month' => 0, 'current_month' => 0, 'total_exp' => 0, 'rem_req' => 0, 'total_req' => 0];
        $head4406Totals = ['sanctioned' => 0, 'released' => 0, 'last_month' => 0, 'current_month' => 0, 'total_exp' => 0, 'rem_req' => 0, 'total_req' => 0];
        $grandTotals = ['sanctioned' => 0, 'released' => 0, 'last_month' => 0, 'current_month' => 0, 'total_exp' => 0, 'rem_req' => 0, 'total_req' => 0];

        foreach ($sections as $sec) {
            $subSanctioned = 0;
            $subReleased = 0;
            $subLast = 0;
            $subCurrent = 0;
            $subTotalExp = 0;
            $subRemReq = 0;
            $subTotalReq = 0;

            $processedSchemes = [];
            foreach ($sec['schemes'] as $sch) {
                $totExp = $sch['last_month'] + $sch['current_month'];
                $remReq = $sch['sanctioned'] - $totExp;
                $totReq = $sch['sanctioned'];
                $pctSanctioned = $sch['sanctioned'] > 0 ? round(($totExp / $sch['sanctioned']) * 100, 2) : 0;
                $pctReleased = $sch['released'] > 0 ? round(($totExp / $sch['released']) * 100, 2) : 0;

                $subSanctioned += $sch['sanctioned'];
                $subReleased += $sch['released'];
                $subLast += $sch['last_month'];
                $subCurrent += $sch['current_month'];
                $subTotalExp += $totExp;
                $subRemReq += $remReq;
                $subTotalReq += $totReq;

                $processedSchemes[] = [
                    'name' => $sch['name'],
                    'sanctioned' => $sch['sanctioned'],
                    'released' => $sch['released'],
                    'last_month' => $sch['last_month'],
                    'current_month' => $sch['current_month'],
                    'total_exp' => $totExp,
                    'rem_req' => $remReq,
                    'total_req' => $totReq,
                    'pct_sanctioned' => $pctSanctioned,
                    'pct_released' => $pctReleased,
                ];
            }

            $subPctSanc = $subSanctioned > 0 ? round(($subTotalExp / $subSanctioned) * 100, 2) : 0;
            $subPctRel = $subReleased > 0 ? round(($subTotalExp / $subReleased) * 100, 2) : 0;

            $is2406 = str_starts_with($sec['demand_key'], '2406');
            if ($is2406) {
                $head2406Totals['sanctioned'] += $subSanctioned;
                $head2406Totals['released'] += $subReleased;
                $head2406Totals['last_month'] += $subLast;
                $head2406Totals['current_month'] += $subCurrent;
                $head2406Totals['total_exp'] += $subTotalExp;
                $head2406Totals['rem_req'] += $subRemReq;
                $head2406Totals['total_req'] += $subTotalReq;
            } else {
                $head4406Totals['sanctioned'] += $subSanctioned;
                $head4406Totals['released'] += $subReleased;
                $head4406Totals['last_month'] += $subLast;
                $head4406Totals['current_month'] += $subCurrent;
                $head4406Totals['total_exp'] += $subTotalExp;
                $head4406Totals['rem_req'] += $subRemReq;
                $head4406Totals['total_req'] += $subTotalReq;
            }

            $grandTotals['sanctioned'] += $subSanctioned;
            $grandTotals['released'] += $subReleased;
            $grandTotals['last_month'] += $subLast;
            $grandTotals['current_month'] += $subCurrent;
            $grandTotals['total_exp'] += $subTotalExp;
            $grandTotals['rem_req'] += $subRemReq;
            $grandTotals['total_req'] += $subTotalReq;

            $processedSections[] = [
                'demand_key' => $sec['demand_key'],
                'demand_title' => $sec['demand_title'],
                'schemes' => $processedSchemes,
                'subtotal' => [
                    'sanctioned' => $subSanctioned,
                    'released' => $subReleased,
                    'last_month' => $subLast,
                    'current_month' => $subCurrent,
                    'total_exp' => $subTotalExp,
                    'rem_req' => $subRemReq,
                    'total_req' => $subTotalReq,
                    'pct_sanctioned' => $subPctSanc,
                    'pct_released' => $subPctRel,
                ]
            ];
        }

        $head2406Totals['pct_sanctioned'] = $head2406Totals['sanctioned'] > 0 ? round(($head2406Totals['total_exp'] / $head2406Totals['sanctioned']) * 100, 2) : 0;
        $head2406Totals['pct_released'] = $head2406Totals['released'] > 0 ? round(($head2406Totals['total_exp'] / $head2406Totals['released']) * 100, 2) : 0;

        $head4406Totals['pct_sanctioned'] = $head4406Totals['sanctioned'] > 0 ? round(($head4406Totals['total_exp'] / $head4406Totals['sanctioned']) * 100, 2) : 0;
        $head4406Totals['pct_released'] = $head4406Totals['released'] > 0 ? round(($head4406Totals['total_exp'] / $head4406Totals['released']) * 100, 2) : 0;

        $grandTotals['pct_sanctioned'] = $grandTotals['sanctioned'] > 0 ? round(($grandTotals['total_exp'] / $grandTotals['sanctioned']) * 100, 2) : 0;
        $grandTotals['pct_released'] = $grandTotals['released'] > 0 ? round(($grandTotals['total_exp'] / $grandTotals['released']) * 100, 2) : 0;

        return [
            'report_type' => 'summary_abstract',
            'report_title' => "Abstract of SUMMARY of the month {$month}-" . date('Y'),
            'division_name' => $profile->display_name,
            'division_name_gujarati' => $profile->display_name_gujarati,
            'officer_name' => $profile->display_officer_name,
            'officer_designation' => $profile->display_designation,
            'officer_designation_gujarati' => $profile->display_designation_gujarati,
            'logo_url' => $profile->logo_url,
            'address' => $profile->formatted_address,
            'month' => $month,
            'payment_mode' => $paymentMode,
            'sections' => $processedSections,
            'head_2406_totals' => $head2406Totals,
            'head_4406_totals' => $head4406Totals,
            'grand_totals' => $grandTotals,
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    private function compileDetailedSummaryData(
        \App\Models\OfficeProfile $profile,
        string $month,
        string $paymentMode,
        string $reportType
    ): array {
        $isOnlyLc = ($reportType === 'summary_only_lc');

        $schemes = [
            [
                'operating_head' => '2406/26 Revenue and Scheme: 02- Divisional',
                'items' => [
                    ['sr_no' => 1, 'item' => 'Class-2', 'obj_class' => 'Class-2', 'model' => '1300- OE', 'allotment' => 700000.00, 'p_exp' => 22805.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 2, 'item' => 'Class-3', 'obj_class' => 'Class-3', 'model' => '2100- Material And Supply', 'allotment' => 300000.00, 'p_exp' => 0.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 3, 'item' => 'Class-3', 'obj_class' => 'Class-3', 'model' => '2600- Adv.and Pub.', 'allotment' => 70000.00, 'p_exp' => 0.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 4, 'item' => 'Class-3', 'obj_class' => 'Class-3', 'model' => '2700- Minor Works', 'allotment' => 300000.00, 'p_exp' => 0.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 5, 'item' => 'Class-3', 'obj_class' => 'Class-3', 'model' => '3001- Outsourcing Servicies (Man power)', 'allotment' => 1000000.00, 'p_exp' => 240666.00, 'c_exp' => 118360.00, 'req_change' => 0.00],
                ],
            ],
            [
                'operating_head' => '2406/26 Revenue and Scheme: 17- Gujarat Community Forestry Scheme NT',
                'items' => [
                    ['sr_no' => 1, 'item' => 'Class-1', 'obj_class' => 'Wages', 'model' => '0200- Wages (Nursery Maintenance)', 'allotment' => 25000000.00, 'p_exp' => 4090832.00, 'c_exp' => 3282864.00, 'req_change' => 0.00],
                    ['sr_no' => 2, 'item' => 'Class-2', 'obj_class' => 'Class-2', 'model' => '1300- OE (Field Supervision)', 'allotment' => 420000.00, 'p_exp' => 87368.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 3, 'item' => 'Class-3', 'obj_class' => 'Class-3', 'model' => '2700- Minor Works (Soil Work)', 'allotment' => 5500000.00, 'p_exp' => 0.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                    ['sr_no' => 4, 'item' => 'Class-4', 'obj_class' => 'Class-4', 'model' => '3800- Assistance to Beneficiaries', 'allotment' => 2000000.00, 'p_exp' => 0.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                ],
            ],
            [
                'operating_head' => '4406/26 Capital and Scheme: 10- FST- 8 Community Forestry Scheme',
                'items' => [
                    ['sr_no' => 1, 'item' => 'Class-6', 'obj_class' => 'Major Works', 'model' => '5300- Plantation & Afforestation Works', 'allotment' => 380000000.00, 'p_exp' => 12348438.00, 'c_exp' => 35948519.00, 'req_change' => 0.00],
                    ['sr_no' => 2, 'item' => 'Class-6', 'obj_class' => 'Capital', 'model' => '6000- Motor Vehicle & Maintenance', 'allotment' => 13194261.00, 'p_exp' => 356161.00, 'c_exp' => 89418.00, 'req_change' => 0.00],
                ],
            ],
            [
                'operating_head' => '4406/96 Tribal Capital and Scheme: 06- Fst-08 Gujarat Community Forestry Project',
                'items' => [
                    ['sr_no' => 1, 'item' => 'Class-6', 'obj_class' => 'Major Works', 'model' => '5300- Major Works (Tribal Plantation)', 'allotment' => 110000000.00, 'p_exp' => 7071503.00, 'c_exp' => 17381809.00, 'req_change' => 0.00],
                    ['sr_no' => 2, 'item' => 'Class-6', 'obj_class' => 'Capital', 'model' => '6000- Other Capital Expenditure', 'allotment' => 5000000.00, 'p_exp' => 105036.00, 'c_exp' => 421887.00, 'req_change' => 0.00],
                    ['sr_no' => 3, 'item' => 'Class-6', 'obj_class' => 'Capital', 'model' => 'Motor Vehicle POL', 'allotment' => 1183725.00, 'p_exp' => 81642.00, 'c_exp' => 0.00, 'req_change' => 0.00],
                ],
            ],
        ];

        $processedGroups = [];
        $grandAllotment = 0;
        $grandPExp = 0;
        $grandCExp = 0;
        $grandTotalExp = 0;
        $grandTotReq = 0;
        $grandRemReq = 0;

        foreach ($schemes as $s) {
            $groupAllotment = 0;
            $groupPExp = 0;
            $groupCExp = 0;
            $groupTotalExp = 0;
            $groupTotReq = 0;
            $groupRemReq = 0;

            $items = [];
            foreach ($s['items'] as $item) {
                if ($isOnlyLc && stripos($item['obj_class'], 'OE') !== false && stripos($item['model'], 'OE') !== false) {
                    continue;
                }

                $totExp = $item['p_exp'] + $item['c_exp'];
                $totReq = $item['allotment'] + $item['req_change'];
                $remReq = $totReq - $totExp;

                $groupAllotment += $item['allotment'];
                $groupPExp += $item['p_exp'];
                $groupCExp += $item['c_exp'];
                $groupTotalExp += $totExp;
                $groupTotReq += $totReq;
                $groupRemReq += $remReq;

                $items[] = [
                    'sr_no' => $item['sr_no'],
                    'item' => $item['item'],
                    'obj_class' => $item['obj_class'],
                    'model' => $item['model'],
                    'allotment' => $item['allotment'],
                    'p_exp' => $item['p_exp'],
                    'c_exp' => $item['c_exp'],
                    'total_exp' => $totExp,
                    'tot_req' => $totReq,
                    'req_change' => $item['req_change'],
                    'rem_req' => $remReq,
                ];
            }

            if (!empty($items)) {
                $grandAllotment += $groupAllotment;
                $grandPExp += $groupPExp;
                $grandCExp += $groupCExp;
                $grandTotalExp += $groupTotalExp;
                $grandTotReq += $groupTotReq;
                $grandRemReq += $groupRemReq;

                $processedGroups[] = [
                    'operating_head' => $s['operating_head'],
                    'items' => $items,
                    'totals' => [
                        'allotment' => $groupAllotment,
                        'p_exp' => $groupPExp,
                        'c_exp' => $groupCExp,
                        'total_exp' => $groupTotalExp,
                        'tot_req' => $groupTotReq,
                        'req_change' => 0.00,
                        'rem_req' => $groupRemReq,
                    ]
                ];
            }
        }

        $titleSuffix = $isOnlyLc ? '(Only LC / માત્ર મજૂરી ખર્ચ)' : '(Merged with salary / પગાર સહિત)';

        return [
            'report_type' => $reportType,
            'report_title' => "SUMMARY REPORT of the month {$month}-" . date('Y') . " {$titleSuffix}",
            'division_name' => $profile->display_name,
            'division_name_gujarati' => $profile->display_name_gujarati,
            'officer_name' => $profile->display_officer_name,
            'officer_designation' => $profile->display_designation,
            'officer_designation_gujarati' => $profile->display_designation_gujarati,
            'logo_url' => $profile->logo_url,
            'address' => $profile->formatted_address,
            'month' => $month,
            'payment_mode' => $paymentMode,
            'groups' => $processedGroups,
            'grand_totals' => [
                'allotment' => $grandAllotment,
                'p_exp' => $grandPExp,
                'c_exp' => $grandCExp,
                'total_exp' => $grandTotalExp,
                'tot_req' => $grandTotReq,
                'req_change' => 0.00,
                'rem_req' => $grandRemReq,
            ],
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }
}
