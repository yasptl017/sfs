<?php

namespace Tests\Feature;

use App\Models\AllotmentFromCircle;
use App\Models\BudgetCode;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use App\Support\MonthlyReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Expenditure rules shared by the Summary and Monthly reports (proven against the spreadsheets). */
class ExpenditureReportTest extends TestCase
{
    use RefreshDatabase;

    private int $fy;

    private User $division;

    private User $range;

    private BudgetCode $a3;

    protected function setUp(): void
    {
        parent::setUp();
        $year = FinancialYear::create(['code' => '2026-27', 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31', 'is_current' => true]);
        $this->fy = $year->id;
        $this->division = User::factory()->create(['role' => 'division']);
        $this->range = User::factory()->create(['role' => 'range', 'division_id' => $this->division->id]);
        $this->a3 = BudgetCode::create(['budget_code' => 'A3', 'operating_head' => '2406/26 Revenue', 'scheme' => '02- Divisional', 'object_class' => 'Class-2', 'object_code' => '1300', 'description' => '1300- Office Expenses', 'model' => '1300- OE', 'payment_mode' => 'IFMS']);
    }

    private function voucher(array $over = []): Voucher
    {
        static $n = 0;
        $n++;

        return Voucher::create(array_merge([
            'financial_year_id' => $this->fy, 'range_id' => $this->range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $n, 'budget_code' => 'A3', 'scheme' => '02- Divisional',
            'party_name' => 'P', 'payable_total_amount' => 1000, 'total_deduction' => 20, 'net_amount' => 980, 'payment_mode' => 'IFMS', 'ifms_bill_type' => 'Simple Receipt',
            'advice_no' => 1, 'treasury_status' => 'Approved', 'treasury_approval_date' => '2026-06-10',
        ], $over));
    }

    public function test_month_windows_cross_the_calendar_year_correctly(): void
    {
        $this->assertSame(['start' => '2026-04-01', 'end' => '2026-04-30', 'prev_end' => '2026-03-31'], ExpenditureReport::window($this->fy, 'Apr'));
        $this->assertSame(['start' => '2027-01-01', 'end' => '2027-01-31', 'prev_end' => '2026-12-31'], ExpenditureReport::window($this->fy, 'Jan'));
        $this->assertSame('2027-03-31', ExpenditureReport::window($this->fy, 'Mar')['end']);
    }

    public function test_expenditure_is_split_by_treasury_approval_month_and_only_counts_approved_vouchers(): void
    {
        $this->voucher(['payable_total_amount' => 1000, 'treasury_approval_date' => '2026-06-10']);          // before the selected month
        $this->voucher(['payable_total_amount' => 2000, 'treasury_approval_date' => '2026-07-01']);          // first day of the month
        $this->voucher(['payable_total_amount' => 4000, 'treasury_approval_date' => '2026-07-31']);          // last day of the month
        $this->voucher(['payable_total_amount' => 8000, 'treasury_approval_date' => '2026-08-01']);          // after
        $this->voucher(['payable_total_amount' => 16000, 'treasury_status' => 'Returned', 'treasury_approval_date' => '2026-07-10']); // not approved
        $this->voucher(['payable_total_amount' => 32000, 'advice_no' => null, 'treasury_status' => null, 'treasury_approval_date' => null]); // not even in an advice

        $row = collect(ExpenditureReport::codeRows($this->fy, 'Jul'))->firstWhere('budget_code', 'A3');

        $this->assertEquals(1000.0, $row->p_exp);
        $this->assertEquals(6000.0, $row->c_exp);
    }

    public function test_allotment_is_the_latest_circle_entry_dated_on_or_before_the_month_end(): void
    {
        foreach ([['2026-05-04', '2026-05-22 10:00:00', 100], ['2026-07-31', '2026-07-31 17:00:00', 300], ['2026-09-29', '2026-09-29 11:00:00', 500]] as [$date, $stamp, $amount]) {
            AllotmentFromCircle::create(['division_id' => $this->division->id, 'budget_code_id' => $this->a3->id, 'amount' => $amount, 'allotment_date' => $date, 'source_time_stamp' => $stamp]);
        }

        $allot = fn (string $m) => collect(ExpenditureReport::codeRows($this->fy, $m))->firstWhere('budget_code', 'A3')->allotment;

        $this->assertEquals(0.0, $allot('Apr'));
        $this->assertEquals(100.0, $allot('Jun'));
        $this->assertEquals(300.0, $allot('Jul'), 'cumulative restatements replace, they are not added');
        $this->assertEquals(500.0, $allot('Nov'));
    }

    public function test_the_payment_mode_filter_splits_the_total(): void
    {
        $this->voucher(['payable_total_amount' => 1000, 'payment_mode' => 'IFMS', 'treasury_approval_date' => '2026-07-10']);
        $this->voucher(['payable_total_amount' => 500, 'payment_mode' => 'SNA', 'treasury_approval_date' => '2026-07-11']);

        $c = fn (string $mode) => collect(ExpenditureReport::codeRows($this->fy, 'Jul', $mode))->firstWhere('budget_code', 'A3')->c_exp;

        $this->assertEquals(1500.0, $c('ALL'));
        $this->assertEquals(1000.0, $c('IFMS'));
        $this->assertEquals(500.0, $c('SNA'));
    }

    public function test_released_is_the_l_c_issued_up_to_the_month_end(): void
    {
        \App\Models\LcEntry::create(['division_id' => $this->division->id, 'scheme' => '02- Divisional', 'class' => 'Class-2', 'entry_date' => '2026-06-30', 'amount' => 700]);
        \App\Models\LcEntry::create(['division_id' => $this->division->id, 'scheme' => '02- Divisional', 'class' => 'Class-2', 'entry_date' => '2026-08-15', 'amount' => 300]);

        $this->assertEquals(700.0, ExpenditureReport::released($this->fy, 'Jul')['02- Divisional']['Class-2']);
        $this->assertEquals(1000.0, ExpenditureReport::released($this->fy, 'Aug')['02- Divisional']['Class-2']);
    }

    public function test_summary_and_monthly_endpoints_report_the_same_expenditure(): void
    {
        $this->voucher(['payable_total_amount' => 2500, 'total_deduction' => 100, 'net_amount' => 2400, 'tds' => 100, 'treasury_approval_date' => '2026-07-10', 'treasury_voucher_no' => '9']);
        $this->actingAs($this->division);

        $summary = $this->postJson(route('division.summary-reports.preview'), ['month' => 'Jul', 'payment_mode' => 'ALL', 'report_type' => 'summary_abstract'])->assertOk()->json('data.grand_totals');
        $monthly = $this->postJson(route('division.monthly-reports.preview'), ['month' => 'Jul', 'payment_mode' => 'IFMS', 'report_type' => 'form_53_new'])->assertOk()->json('data.totals');
        $form53 = $this->postJson(route('division.monthly-reports.preview'), ['month' => 'Jul', 'payment_mode' => 'IFMS', 'report_type' => 'form_53_abstract'])->assertOk()->json('data.d36_sections');

        $this->assertEquals(2500.0, $summary['current_month']);
        $this->assertEquals(2500.0, $monthly['gross']);
        $this->assertEquals(2400.0, $monthly['net']);
        $this->assertEquals(100.0, $monthly['it_tds']);
        $this->assertEquals(2500.0, $form53['grand_total_during_month']);
    }

    public function test_deduction_schedule_groups_parties_and_skips_salary_vouchers(): void
    {
        $this->voucher(['party_name' => 'Balaji', 'pan_card_no' => 'AAAPB1234C', 'amount_as_per_approved_rate' => 10000, 'tds' => 100, 'voucher_no_final' => 'SR-2', 'treasury_approval_date' => '2026-07-10']);
        $this->voucher(['party_name' => 'Balaji', 'pan_card_no' => 'AAAPB1234C', 'amount_as_per_approved_rate' => 20000, 'tds' => 200, 'voucher_no_final' => 'SR-1', 'treasury_approval_date' => '2026-07-12']);
        $this->voucher(['party_name' => 'Wager', 'entry_type' => 'D.Wagers Salary', 'tds' => 50, 'p_tax' => 200, 'treasury_approval_date' => '2026-07-12']);

        $items = MonthlyReportData::build($this->fy, 'Jul', 'IFMS', [], 'deduction_reports')['tds_items'];

        $this->assertCount(1, $items);
        $this->assertSame('Balaji', $items[0]['party_name']);
        $this->assertEquals(30000.0, $items[0]['taxable_amount']);
        $this->assertEquals(300.0, $items[0]['tds_amount']);
        $this->assertSame('SR-1, SR-2', $items[0]['voucher_no']);
    }

    public function test_an_unknown_scheme_is_still_printed_on_form_53(): void
    {
        BudgetCode::create(['budget_code' => 'Z9', 'operating_head' => '2406/26 Revenue', 'scheme' => 'A Brand New Scheme', 'object_class' => 'Class-4', 'object_code' => '3800', 'description' => '3800- Assistance', 'model' => 'm', 'payment_mode' => 'IFMS']);
        $this->voucher(['budget_code' => 'Z9', 'payable_total_amount' => 777, 'treasury_approval_date' => '2026-07-10']);

        $form = MonthlyReportData::build($this->fy, 'Jul', 'IFMS', [], 'form_53_abstract')['d36_sections'];

        $this->assertEquals(777.0, $form['grand_total_during_month'], 'every rupee appears somewhere on the form');
        $names = collect($form['demands'])->flatMap(fn ($d) => collect($d['minor_heads'])->pluck('name'))->all();
        $this->assertTrue(collect($names)->contains(fn ($n) => str_contains($n, 'Other schemes')));
    }
}
