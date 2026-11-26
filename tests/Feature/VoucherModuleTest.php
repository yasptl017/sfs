<?php

namespace Tests\Feature;

use App\Models\BudgetCode;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Support\VoucherCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Voucher browser + entry (typed tables). */
class VoucherModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $division;

    private User $rangeA;

    private User $rangeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->division = User::factory()->create(['role' => 'division', 'name' => 'Division']);
        $this->rangeA = User::factory()->create(['role' => 'range', 'name' => 'Idar', 'division_id' => $this->division->id]);
        $this->rangeB = User::factory()->create(['role' => 'range', 'name' => 'Modasa', 'division_id' => $this->division->id]);
        BudgetCode::create(['budget_code' => 'A3', 'scheme' => '02- Divisional', 'object_class' => 'Class-2', 'model' => '1300- OE', 'scheme_year' => '2026-27', 'payment_mode' => 'IFMS']);
    }

    private function voucher(User $range, array $over = []): Voucher
    {
        static $n = 0;
        $n++;

        return Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'range_name' => $range->name, 'entry_type' => 'Tender Entry', 'serial_number' => $n,
            'budget_code' => 'A3', 'party_code' => 'P'.$n, 'party_name' => 'Party '.$n, 'entry_month' => 'Jun', 'sub_total' => 1000, 'payable_total_amount' => 1000,
            'total_deduction' => 20, 'net_amount' => 980, 'payment_mode' => 'IFMS', 'received_at_division' => 'Received',
        ], $over));
    }

    private function payload(array $v = [], ?array $items = null): array
    {
        return ['v' => array_merge(['entry_month' => 'Jun', 'budget_code' => 'A3', 'party_code' => 'P1', 'party_name' => 'Test Party', 'approved_percent' => 100], $v),
            'items' => $items ?? [['description' => 'Work', 'quantity' => 10, 'rate' => 50]]];
    }

    // ---------------------------------------------------------------- browser
    public function test_each_role_sees_only_its_own_scope(): void
    {
        $this->voucher($this->rangeA);
        $this->voucher($this->rangeA);
        $this->voucher($this->rangeB);
        $outsider = User::factory()->create(['role' => 'range', 'division_id' => User::factory()->create(['role' => 'division'])->id]);
        $this->voucher($outsider);

        $this->actingAs($this->rangeA)->get(route('vouchers.index'))->assertOk()->assertSee('>2<', false);
        $this->actingAs($this->division)->get(route('vouchers.index'))->assertOk()->assertSee('>3<', false);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('vouchers.index'))->assertOk()->assertSee('>4<', false);
    }

    public function test_a_range_cannot_open_another_ranges_voucher(): void
    {
        $other = $this->voucher($this->rangeB);

        $this->actingAs($this->rangeA)->get(route('vouchers.show', $other))->assertForbidden();
        $this->actingAs($this->division)->get(route('vouchers.show', $other))->assertOk();
    }

    public function test_search_and_filters_narrow_the_list(): void
    {
        $this->voucher($this->rangeA, ['party_name' => 'Balaji Enterprise']);
        $this->voucher($this->rangeA, ['party_name' => 'Other Traders', 'entry_type' => 'Free Entry']);

        $this->actingAs($this->rangeA)->get(route('vouchers.index', ['q' => 'Balaji']))->assertSee('Balaji Enterprise')->assertDontSee('Other Traders');
        $this->actingAs($this->rangeA)->get(route('vouchers.index', ['entry_type' => 'Free Entry']))->assertSee('Other Traders')->assertDontSee('Balaji Enterprise');
    }

    public function test_switching_the_financial_year_changes_what_is_listed(): void
    {
        $this->voucher($this->rangeA);
        $other = \App\Models\FinancialYear::create(['code' => '2020-21', 'starts_on' => '2020-04-01', 'ends_on' => '2021-03-31', 'is_current' => false]);

        $this->actingAs($this->rangeA)->post(route('financial-year.switch'), ['financial_year_id' => $other->id])->assertRedirect();
        $this->actingAs($this->rangeA)->get(route('vouchers.index'))->assertSee('No vouchers match');
    }

    // ---------------------------------------------------------------- entry
    public function test_a_range_can_create_a_voucher_with_calculated_totals(): void
    {
        $this->actingAs($this->rangeA)->post(route('finance.tender-entries.store'), $this->payload(
            ['sgst' => 5, 'cgst' => 5, 'tds' => 10, 'deduction_sgst' => 2],
            [['description' => 'A', 'quantity' => 10, 'rate' => 50], ['description' => 'B (typed amount)', 'amount' => 100]]
        ))->assertRedirect();

        $v = Voucher::firstOrFail();
        $this->assertSame(1, (int) $v->serial_number);
        $this->assertEquals(600.0, (float) $v->sub_total);
        $this->assertEquals(610.0, (float) $v->payable_total_amount);
        $this->assertEquals(12.0, (float) $v->total_deduction);
        $this->assertEquals(598.0, (float) $v->net_amount);
        $this->assertSame('02- Divisional', $v->scheme);
        $this->assertSame($this->rangeA->email, $v->entered_by);
        $this->assertCount(2, $v->items);
    }

    public function test_serial_numbers_continue_per_range_and_type(): void
    {
        foreach ([1, 2] as $i) {
            $this->actingAs($this->rangeA)->post(route('finance.tender-entries.store'), $this->payload());
        }
        $this->actingAs($this->rangeA)->post(route('finance.free-entries.store'), $this->payload());
        $this->actingAs($this->rangeB)->post(route('finance.tender-entries.store'), $this->payload());

        $this->assertSame([1, 2], Voucher::where('range_id', $this->rangeA->id)->where('entry_type', 'Tender Entry')->orderBy('serial_number')->pluck('serial_number')->map(fn ($x) => (int) $x)->all());
        $this->assertSame(1, (int) Voucher::where('range_id', $this->rangeA->id)->where('entry_type', 'Free Entry')->value('serial_number'));
        $this->assertSame(1, (int) Voucher::where('range_id', $this->rangeB->id)->value('serial_number'));
    }

    public function test_a_typed_amount_wins_over_quantity_times_rate(): void
    {
        // survival-based payment: 670 surviving plants x 4 = 2,680 on the line, but 4,000 is paid
        $this->actingAs($this->rangeA)->post(route('finance.sf-beneficiary-entries.store'), $this->payload([], [['description' => 'RDFL', 'quantity' => 670, 'rate' => 4, 'amount' => 4000]]))->assertRedirect();

        $this->assertEquals(4000.0, (float) Voucher::firstOrFail()->sub_total);
    }

    public function test_editing_without_changing_amounts_keeps_the_stored_totals_exactly(): void
    {
        $this->actingAs($this->rangeA)->post(route('finance.tender-entries.store'), $this->payload());
        $v = Voucher::firstOrFail();
        // a migrated voucher can carry totals that differ by paise from a recalculation
        $v->update(['sub_total' => 499.99, 'payable_total_amount' => 499.99, 'net_amount' => 499.99]);

        $this->actingAs($this->rangeA)->put(route('finance.tender-entries.update', $v), $this->payload(['party_name' => 'Renamed Party']))->assertRedirect();

        $v->refresh();
        $this->assertSame('Renamed Party', $v->party_name);
        $this->assertEquals(499.99, (float) $v->sub_total);
        $this->assertEquals(499.99, (float) $v->net_amount);
    }

    public function test_changing_an_amount_input_recalculates_and_keeps_the_old_version(): void
    {
        $this->actingAs($this->rangeA)->post(route('finance.tender-entries.store'), $this->payload());
        $v = Voucher::firstOrFail();

        $this->actingAs($this->rangeA)->put(route('finance.tender-entries.update', $v), $this->payload([], [['description' => 'Work', 'quantity' => 20, 'rate' => 50]]))->assertRedirect();

        $this->assertEquals(1000.0, (float) $v->fresh()->sub_total);
        $version = $v->versions()->firstOrFail();
        $this->assertSame('before_edit', $version->version_kind);
        $this->assertContains('Item Details', $version->diff_fields);
        $this->assertEquals(500.0, (float) $version->olderValue('Sub Total'));
    }

    public function test_deleting_keeps_an_audit_copy(): void
    {
        $this->actingAs($this->rangeA)->post(route('finance.tender-entries.store'), $this->payload());
        $v = Voucher::firstOrFail();

        $this->actingAs($this->rangeA)->delete(route('finance.tender-entries.destroy', $v))->assertRedirect();

        $this->assertSame(0, Voucher::count());
        $this->assertDatabaseHas('voucher_versions', ['version_kind' => 'deleted', 'serial_number' => 1, 'voucher_id' => null]);
    }

    public function test_entry_is_restricted_by_role_ownership_and_advice(): void
    {
        $mine = $this->voucher($this->rangeA);
        $theirs = $this->voucher($this->rangeB);
        $inAdvice = $this->voucher($this->rangeA, ['advice_no' => 5]);

        $this->actingAs($this->division)->get(route('finance.tender-entries.create'))->assertForbidden();
        $this->actingAs($this->rangeA)->get(route('finance.tender-entries.edit', $mine))->assertOk();
        $this->actingAs($this->rangeA)->get(route('finance.tender-entries.edit', $theirs))->assertForbidden();
        $this->actingAs($this->rangeA)->get(route('finance.tender-entries.edit', $inAdvice))->assertForbidden();
        $this->actingAs($this->rangeA)->delete(route('finance.tender-entries.destroy', $inAdvice))->assertForbidden();
    }

    public function test_a_voucher_needs_items_and_a_known_budget_code(): void
    {
        $this->actingAs($this->rangeA)->postJson(route('finance.tender-entries.store'), $this->payload([], []))->assertUnprocessable();
        $this->actingAs($this->rangeA)->postJson(route('finance.tender-entries.store'), $this->payload(['budget_code' => 'NOPE']))->assertUnprocessable();
    }

    public function test_the_voucher_calculator_rules(): void
    {
        $t = VoucherCalculator::totals(['approved_percent' => 90, 'sgst' => 10, 'adjustment' => -5, 'tds' => 20, 'gpf' => 5], [['quantity' => 10, 'rate' => 100], ['amount' => 500]]);

        $this->assertSame(1500.0, $t['sub_total']);
        $this->assertSame(1350.0, $t['amount_as_per_approved_rate']);
        $this->assertSame(1355.0, $t['payable_total_amount']);
        $this->assertSame(25.0, $t['total_deduction']);
        $this->assertSame(1330.0, $t['net_amount']);
        $this->assertSame(1500.0, VoucherCalculator::totals([], [['quantity' => 3, 'rate' => 500]])['sub_total'], 'blank approved % means 100');
    }
}
