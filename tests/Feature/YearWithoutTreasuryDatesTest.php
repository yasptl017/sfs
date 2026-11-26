<?php

namespace Tests\Feature;

use App\Models\AllotmentFromCircle;
use App\Models\BudgetCode;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\Voucher;
use App\Support\BudgetPosition;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use App\Support\MonthlyReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Financial year 2025-26: the register records the treasury result only as "Pass = P" + "Approval Month" (no date),
 * final voucher numbers are plain 1..n per month, the adjustment ledger is missing (net per code), pay slips come from the D.Wager Salary register.
 */
class YearWithoutTreasuryDatesTest extends TestCase
{
    use RefreshDatabase;

    private FinancialYear $fy;

    private User $division;

    private User $range;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fy = FinancialYear::create(['code' => '2025-26', 'starts_on' => '2025-04-01', 'ends_on' => '2026-03-31', 'is_current' => false]);
        $this->division = User::factory()->create(['role' => 'division', 'name' => 'Division']);
        $this->range = User::factory()->create(['role' => 'range', 'name' => 'Idar', 'division_id' => $this->division->id]);
        BudgetCode::create(['financial_year_id' => $this->fy->id, 'budget_code' => 'A3', 'scheme' => 'Divisional', 'object_class' => 'Class-2', 'model' => 'OE']);
    }

    private function voucher(array $over = []): Voucher
    {
        static $n = 0;
        $n++;

        return Voucher::create(array_merge([
            'financial_year_id' => $this->fy->id, 'range_id' => $this->range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $n, 'budget_code' => 'A3', 'party_code' => 'P1', 'party_name' => 'Party',
            'scheme' => 'Divisional', 'entry_month' => 'May', 'payable_total_amount' => 1000, 'total_deduction' => 20, 'net_amount' => 980, 'payment_mode' => 'IFMS',
            'treasury_status' => 'Approved', 'treasury_approval_month' => 'Jun', 'pass_status' => 'P', 'advice_no' => 1,
        ], $over));
    }

    public function test_expenditure_is_split_by_approval_month_when_the_year_has_no_treasury_dates(): void
    {
        $this->voucher(['treasury_approval_month' => 'May', 'payable_total_amount' => 100]);   // before June  -> P
        $this->voucher(['treasury_approval_month' => 'Jun', 'payable_total_amount' => 200]);   // June         -> C
        $this->voucher(['treasury_approval_month' => 'Jul', 'payable_total_amount' => 400]);   // after June   -> neither
        $this->voucher(['treasury_status' => null, 'pass_status' => null, 'treasury_approval_month' => null, 'payable_total_amount' => 800]); // not passed

        $row = collect(ExpenditureReport::codeRows($this->fy->id, 'Jun'))->firstWhere('budget_code', 'A3');

        $this->assertEquals(100, $row->p_exp);
        $this->assertEquals(200, $row->c_exp);
    }

    public function test_a_year_with_dates_still_uses_the_dates(): void
    {
        $this->voucher(['treasury_approval_date' => '2025-06-10', 'treasury_approval_month' => 'Jun', 'payable_total_amount' => 300]);
        $this->voucher(['treasury_approval_date' => '2025-05-10', 'treasury_approval_month' => 'Jun', 'payable_total_amount' => 50]);   // the date wins over the month label

        $row = collect(ExpenditureReport::codeRows($this->fy->id, 'Jun'))->firstWhere('budget_code', 'A3');

        $this->assertEquals(50, $row->p_exp);
        $this->assertEquals(300, $row->c_exp);
    }

    public function test_form_35_lists_numeric_final_numbers_in_order_and_unnumbered_last(): void
    {
        session(['financial_year_id' => $this->fy->id]);
        CurrentFinancialYear::forget();
        $this->voucher(['voucher_no_final' => '10']);
        $this->voucher(['voucher_no_final' => '2']);
        $this->voucher(['voucher_no_final' => null]);
        $this->voucher(['voucher_no_final' => '2', 'payment_mode' => 'PFMS']);

        $json = $this->actingAs($this->division)->withSession(['financial_year_id' => $this->fy->id])->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'payment_mode' => 'IFMS', 'report_type' => 'form_35'])->assertOk()->json('data');

        $this->assertSame(['2', '10', null], array_column($json['entries'], 'final_voucher_no'));
        $this->assertSame(3, $json['totals']['count']);
        $pfms = $this->actingAs($this->division)->withSession(['financial_year_id' => $this->fy->id])->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'payment_mode' => 'PFMS', 'report_type' => 'form_35'])->assertOk()->json('data');
        $this->assertSame(1, $pfms['totals']['count']);
    }

    public function test_net_adjustments_recovered_from_the_sheet_count_in_the_budget_position(): void
    {
        session(['financial_year_id' => $this->fy->id]);
        CurrentFinancialYear::forget();
        $b = BudgetCode::where('budget_code', 'A3')->first();
        AllotmentFromCircle::create(['financial_year_id' => $this->fy->id, 'division_id' => $this->division->id, 'budget_code_id' => $b->id, 'amount' => 1000, 'source_time_stamp' => '2025-05-01 10:00:00']);
        DB::table('allotment_net_adjustments')->insert(['financial_year_id' => $this->fy->id, 'division_id' => $this->division->id, 'budget_code_id' => $b->id, 'amount' => -250, 'created_at' => now(), 'updated_at' => now()]);

        $p = BudgetPosition::for($this->division->id, $b->id);

        $this->assertEquals(1000, $p['circle']);
        $this->assertEquals(250, $p['out']);
        $this->assertEquals(750, $p['adjusted']);
    }

    public function test_pay_slips_take_days_worked_from_the_salary_register(): void
    {
        $this->voucher(['entry_type' => 'D.Wagers Salary', 'party_code' => 'DW1/1', 'party_name' => 'Valand D', 'description' => 'salary of Valand, April-2025 ના માસમાં 26 + 4 = 30 દિવસ ', 'treasury_approval_month' => 'Jun']);
        DB::table('d_wager_salaries')->insert(['financial_year_id' => $this->fy->id, 'range_id' => $this->range->id, 'serial_number' => 1, 'd_wager_code' => 'DW1/1', 'salary_year' => 2025, 'salary_month' => 'April',
            'working_days' => 26, 'no_of_leaves' => 4, 'basic_pay' => 26800, 'pay_scale' => '19900-63200', 'created_at' => now(), 'updated_at' => now()]);
        $this->voucher(['entry_type' => 'D.Wagers Salary', 'party_code' => 'DW9/9', 'party_name' => 'No register row', 'description' => 'salary, April-2025 ના', 'treasury_approval_month' => 'Jun']);

        $slips = MonthlyReportData::build($this->fy->id, 'Jun', 'IFMS', [], 'pay_slips')['pay_slips_list'];

        $this->assertCount(2, $slips);
        $this->assertEquals(26.0, $slips[0]['days_worked']);
        $this->assertEquals(4.0, $slips[0]['leaves']);
        $this->assertEquals(26800.0, $slips[0]['basic_pay']);
        $this->assertNull($slips[1]['days_worked'], 'no register row: nothing is invented');
    }
}
