<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PositionReportController;
use App\Models\AllotmentAdjustment;
use App\Models\AllotmentFromCircle;
use App\Models\AllotmentToRange;
use App\Models\BudgetCode;
use App\Models\PartyRegistration;
use App\Models\User;
use App\Models\Voucher;
use App\Support\BudgetPosition;
use App\Support\CurrentFinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Division advice workflow, allotment rules, position report and registers. */
class AdviceAndAllotmentTest extends TestCase
{
    use RefreshDatabase;

    private User $division;

    private User $rangeA;

    private User $rangeB;

    private BudgetCode $a3;

    private BudgetCode $a6;

    protected function setUp(): void
    {
        parent::setUp();
        $this->division = User::factory()->create(['role' => 'division', 'name' => 'Division']);
        $this->rangeA = User::factory()->create(['role' => 'range', 'name' => 'Idar', 'division_id' => $this->division->id]);
        $this->rangeB = User::factory()->create(['role' => 'range', 'name' => 'Modasa', 'division_id' => $this->division->id]);
        $this->a3 = BudgetCode::create(['budget_code' => 'A3', 'scheme' => 'Divisional', 'object_class' => 'Class-2', 'model' => 'OE', 'payment_mode' => 'IFMS']);
        $this->a6 = BudgetCode::create(['budget_code' => 'A6', 'scheme' => 'Divisional', 'object_class' => 'Class-3', 'model' => 'Material', 'payment_mode' => 'IFMS']);
    }

    private function voucher(User $range, array $over = []): Voucher
    {
        static $n = 100;
        $n++;

        return Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'range_name' => $range->name, 'entry_type' => 'Tender Entry', 'serial_number' => $n,
            'budget_code' => 'A3', 'party_code' => 'P', 'party_name' => 'Party', 'entry_month' => 'Jun', 'sub_total' => 1000, 'payable_total_amount' => 1000,
            'total_deduction' => 20, 'net_amount' => 980, 'payment_mode' => 'IFMS', 'received_at_division' => 'Received',
        ], $over));
    }

    private function circle(BudgetCode $b, float $amount, string $stamp): void
    {
        AllotmentFromCircle::create(['division_id' => $this->division->id, 'budget_code_id' => $b->id, 'amount' => $amount, 'source_time_stamp' => $stamp]);
    }

    // ---------------------------------------------------------------- allotment rules (proven against the spreadsheet)
    public function test_circle_allotment_is_the_latest_entry_not_the_sum(): void
    {
        $this->circle($this->a3, 1754900, '2026-07-31 17:06:00');
        $this->circle($this->a3, 6732763, '2026-10-21 14:34:00');
        $this->circle($this->a3, 3667420, '2026-09-28 17:56:00'); // entered later in the file, but older by time stamp

        $this->assertEquals(6732763.0, BudgetPosition::for($this->division->id, $this->a3->id)['circle']);
    }

    public function test_adjusted_and_pending_allotment(): void
    {
        $this->circle($this->a3, 1000000, '2026-06-01 10:00:00');
        $this->circle($this->a6, 200000, '2026-06-01 10:00:00');
        AllotmentAdjustment::create(['division_id' => $this->division->id, 'from_budget_code_id' => $this->a3->id, 'to_budget_code_id' => $this->a6->id, 'amount' => 100000]);
        AllotmentToRange::create(['division_id' => $this->division->id, 'range_id' => $this->rangeA->id, 'budget_code_id' => $this->a3->id, 'amount' => 500000]);
        AllotmentToRange::create(['division_id' => $this->division->id, 'range_id' => $this->rangeB->id, 'budget_code_id' => $this->a3->id, 'amount' => 50000]);
        AllotmentToRange::create(['division_id' => $this->division->id, 'range_id' => $this->rangeB->id, 'budget_code_id' => $this->a3->id, 'amount' => -10000]); // correction

        $p = BudgetPosition::for($this->division->id, $this->a3->id);
        $this->assertEquals(900000.0, $p['adjusted']);       // 1,000,000 - 100,000 given
        $this->assertEquals(540000.0, $p['distributed']);    // increments are summed, negatives included
        $this->assertEquals(360000.0, $p['pending']);
        $this->assertEquals(300000.0, BudgetPosition::for($this->division->id, $this->a6->id)['adjusted']);
    }

    public function test_distribution_and_adjustment_are_limited_by_the_pending_allotment(): void
    {
        $this->circle($this->a3, 100000, '2026-06-01 10:00:00');
        $this->circle($this->a6, 10000, '2026-06-01 10:00:00');

        $this->actingAs($this->division)->postJson(route('division.allotments-to-range.store'), ['budget_code_id' => $this->a3->id, 'allocations' => [['range_id' => $this->rangeA->id, 'allotment' => 100001]]])->assertUnprocessable();
        $this->actingAs($this->division)->post(route('division.allotments-to-range.store'), ['budget_code_id' => $this->a3->id, 'allocations' => [['range_id' => $this->rangeA->id, 'allotment' => 60000]]])->assertRedirect();
        $this->assertEquals(60000.0, (float) AllotmentToRange::sum('amount'));

        $this->actingAs($this->division)->postJson(route('division.allotment-adjustments.store'), ['from_budget_code_id' => $this->a3->id, 'to_budget_code_id' => $this->a6->id, 'amount' => 40001])->assertUnprocessable();
        $this->actingAs($this->division)->post(route('division.allotment-adjustments.store'), ['from_budget_code_id' => $this->a3->id, 'to_budget_code_id' => $this->a6->id, 'amount' => 40000])->assertRedirect();
        $this->assertEquals(50000.0, BudgetPosition::for($this->division->id, $this->a6->id)['adjusted']);
        $this->assertEquals(0.0, BudgetPosition::for($this->division->id, $this->a3->id)['pending']);
    }

    public function test_position_report_counts_entered_and_booked_like_the_sheet(): void
    {
        $this->circle($this->a3, 100000, '2026-06-01 10:00:00');
        AllotmentToRange::create(['division_id' => $this->division->id, 'range_id' => $this->rangeA->id, 'budget_code_id' => $this->a3->id, 'amount' => 5000]);
        $this->voucher($this->rangeA, ['payable_total_amount' => 1000]);                                   // entered
        $this->voucher($this->rangeA, ['payable_total_amount' => 2000, 'advice_no' => 1]);                  // entered + booked
        $this->voucher($this->rangeA, ['payable_total_amount' => 4000, 'rejected_reason' => 'no grant']);   // rejected: neither
        $this->voucher($this->rangeA, ['payable_total_amount' => 8000, 'only_in_older_export' => true]);    // flagged

        $row = collect(PositionReportController::rows(CurrentFinancialYear::id()))->firstWhere('range_id', $this->rangeA->id);
        $this->assertEquals(5000.0, $row->allotment);
        $this->assertEquals(11000.0, $row->entered);
        $this->assertEquals(2000.0, $row->booked);
        $this->assertEquals(-6000.0, $row->remaining);
        $without = collect(PositionReportController::rows(CurrentFinancialYear::id(), null, false))->firstWhere('range_id', $this->rangeA->id);
        $this->assertEquals(3000.0, $without->entered, 'the sheet does not include the flagged voucher');
    }

    // ---------------------------------------------------------------- advice workflow
    public function test_only_the_division_can_use_the_advice_screens(): void
    {
        $this->actingAs($this->rangeA)->get(route('division.advices.index'))->assertForbidden();
        $this->actingAs($this->division)->get(route('division.advices.index'))->assertOk();
        $this->actingAs($this->division)->get(route('division.advices.pending'))->assertOk();
    }

    public function test_received_vouchers_become_an_advice_with_the_next_number(): void
    {
        $this->voucher($this->rangeA, ['advice_no' => 41, 'ifms_bill_type' => 'Simple Receipt']);
        $a = $this->voucher($this->rangeA);
        $b = $this->voucher($this->rangeB);
        $notReceived = $this->voucher($this->rangeA, ['received_at_division' => 'Not Received']);
        $rejected = $this->voucher($this->rangeA, ['rejected_reason' => 'x']);

        $this->actingAs($this->division)->post(route('division.advices.store'), ['voucher_ids' => [$a->id, $b->id], 'bill_type' => 'Simple Receipt', 'advice_date' => '2026-11-24'])->assertRedirect(route('division.advices.show', 42));

        foreach ([$a, $b] as $v) {
            $v->refresh();
            $this->assertSame(42, (int) $v->advice_no);
            $this->assertSame('42/'.CurrentFinancialYear::model()->code, $v->bill_register_no);
            $this->assertSame('Simple Receipt', $v->ifms_bill_type);
        }
        $this->assertDatabaseCount('voucher_versions', 2);
        $this->actingAs($this->division)->postJson(route('division.advices.store'), ['voucher_ids' => [$notReceived->id], 'bill_type' => 'Simple Receipt', 'advice_date' => '2026-11-24'])->assertUnprocessable();
        $this->actingAs($this->division)->postJson(route('division.advices.store'), ['voucher_ids' => [$rejected->id], 'bill_type' => 'Simple Receipt', 'advice_date' => '2026-11-24'])->assertUnprocessable();
    }

    public function test_sna_and_ifms_vouchers_cannot_share_an_advice_and_sna_has_its_own_series(): void
    {
        $ifms = $this->voucher($this->rangeA);
        $sna = $this->voucher($this->rangeA, ['payment_mode' => 'SNA']);

        $this->actingAs($this->division)->postJson(route('division.advices.store'), ['voucher_ids' => [$sna->id], 'bill_type' => 'Contingency', 'advice_date' => '2026-11-24'])->assertUnprocessable();
        $this->actingAs($this->division)->post(route('division.advices.store'), ['voucher_ids' => [$sna->id], 'bill_type' => 'SNA', 'advice_date' => '2026-11-24'])->assertRedirect();
        $this->actingAs($this->division)->post(route('division.advices.store'), ['voucher_ids' => [$ifms->id], 'bill_type' => 'Contingency', 'advice_date' => '2026-11-24'])->assertRedirect();

        $this->assertSame(5001, (int) $sna->fresh()->advice_no);
        $this->assertSame(1, (int) $ifms->fresh()->advice_no);
    }

    public function test_marking_vouchers_received_makes_them_available(): void
    {
        $v = $this->voucher($this->rangeA, ['received_at_division' => 'Not Received']);

        $this->actingAs($this->division)->post(route('division.advices.receive'), ['voucher_ids' => [$v->id]])->assertRedirect();

        $this->assertSame('Received', $v->fresh()->received_at_division);
    }

    public function test_treasury_gst_final_numbers_and_delete_guard(): void
    {
        $a = $this->voucher($this->rangeA, ['advice_no' => 7, 'ifms_bill_type' => 'Contingency', 'voucher_no_final' => 'CONT-9']);
        $b = $this->voucher($this->rangeB, ['advice_no' => 7, 'ifms_bill_type' => 'Contingency']);

        $this->actingAs($this->division)->put(route('division.advices.treasury', 7), ['treasury_voucher_no' => '75', 'treasury_approval_date' => '2026-06-29', 'treasury_status' => 'Approved', 'epayment_code' => 'E1'])->assertRedirect();
        $this->actingAs($this->division)->put(route('division.advices.gst', 7), ['gst_challan_no' => 'GC1'])->assertRedirect();
        $this->actingAs($this->division)->post(route('division.advices.final', 7))->assertRedirect();

        foreach ([$a, $b] as $v) {
            $v->refresh();
            $this->assertSame('75', $v->treasury_voucher_no);
            $this->assertSame('Jun', $v->treasury_approval_month);
            $this->assertSame('GC1', $v->gst_challan_no);
        }
        $this->assertSame('CONT-10', $b->voucher_no_final, 'continues after the highest existing CONT- number');

        $this->actingAs($this->division)->deleteJson(route('division.advices.destroy', 7))->assertUnprocessable();   // approved by treasury
        $this->actingAs($this->division)->put(route('division.advices.treasury', 7), ['treasury_voucher_no' => '75', 'treasury_approval_date' => '2026-06-29', 'treasury_status' => 'Pending'])->assertRedirect();
        $this->actingAs($this->division)->delete(route('division.advices.destroy', 7))->assertRedirect(route('division.advices.index'));

        $b->refresh();
        $this->assertNull($b->advice_no);
        $this->assertNull($b->treasury_voucher_no);
        $this->assertSame('Received', $b->received_at_division, 'deleting an advice returns vouchers to "ready to process", not "not received"');
    }

    // ---------------------------------------------------------------- registers
    public function test_registers_are_scoped_by_role(): void
    {
        PartyRegistration::create(['range_id' => $this->rangeA->id, 'serial_number' => 1, 'party_code' => 'F1/1', 'party_name' => 'Idar Party', 'party_status' => 'Active']);
        PartyRegistration::create(['range_id' => $this->rangeB->id, 'serial_number' => 1, 'party_code' => 'F2/1', 'party_name' => 'Modasa Party', 'party_status' => 'Active']);

        $this->actingAs($this->rangeA)->get(route('registers.index', 'parties'))->assertOk()->assertSee('Idar Party')->assertDontSee('Modasa Party');
        $this->actingAs($this->division)->get(route('registers.index', 'parties'))->assertOk()->assertSee('Idar Party')->assertSee('Modasa Party');
        $this->actingAs($this->rangeA)->get(route('registers.index', 'budget-position'))->assertForbidden();
        $this->actingAs($this->division)->get(route('registers.index', 'budget-position'))->assertOk();
        $this->actingAs($this->division)->get(route('registers.index', 'no-such-register'))->assertNotFound();
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create(['role' => 'range', 'username' => 'gone', 'password' => 'secret-pass-1', 'is_active' => false]);

        $this->post(route('login.store'), ['username' => 'gone', 'password' => 'secret-pass-1'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $user->forceFill(['is_active' => true])->save();
        $this->post(route('login.store'), ['username' => 'gone', 'password' => 'secret-pass-1'])->assertRedirect();
        $this->assertAuthenticated();
    }
}
