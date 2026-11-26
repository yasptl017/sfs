<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderPrintTest extends TestCase
{
    use RefreshDatabase;

    /** A voucher with one work-order item, as the migrated data has them. */
    private function voucher(User $range, int $serial, array $over = [], array $item = []): Voucher
    {
        $v = Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $serial,
            'party_name' => 'Party '.$serial, 'budget_code' => 'A3', 'scheme' => '02- Divisional', 'entry_month' => 'Aug', 'docket_no' => 1,
        ], $over));
        $v->items()->create(array_merge([
            'line_no' => 1, 'description' => 'Work', 'quantity' => 10, 'unit' => 'Nos', 'rate' => 100, 'amount' => 1000,
            'work_order_no' => 'WO/'.$serial, 'work_order_date' => '2026-08-01', 'work_start_date' => '2026-08-01', 'work_end_date' => '2026-08-10', 'raw_line' => '[]',
        ], $item));

        return $v;
    }

    public function test_range_user_can_access_work_order_print_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.work-order-print.index'));

        $response->assertStatus(200);
        $response->assertSee('Work Order Print:');
        $response->assertSee('Month');
        $response->assertSee('Party wise separate attachments ?');
        $response->assertSee('Separate');
        $response->assertSee('No. of days to extend the work end date by');
        $response->assertSee('Print all dockets of the month ?');
        $response->assertSee('Docket No.');
        $response->assertSee('Print');
        $response->assertSee('These work order reports are prepared from voucher entry.');
        $response->assertSee('Aug');
    }

    public function test_division_user_cannot_access_work_order_print_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.work-order-print.index'));

        $response->assertStatus(403);
    }

    public function test_dockets_endpoint_returns_json(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $this->voucher($rangeUser, 301, ['docket_no' => 11]);
        $this->voucher($rangeUser, 302, ['docket_no' => 12], ['work_order_no' => null]); // no work order: no docket

        $response = $this->actingAs($rangeUser)->getJson(route('finance.work-order-print.dockets', [
            'month' => 'Aug',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
        ]);
        $response->assertJsonPath('dockets', ['11']);
    }

    public function test_preview_endpoint_calculates_extended_dates_and_amounts(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $this->voucher($rangeUser, 303, ['party_name' => 'Somnath Forestry Contractors', 'docket_no' => 2, 'place' => 'Plot No. 15'], ['description' => 'Trenching and Pit Digging', 'quantity' => 1500, 'unit' => 'Pits', 'rate' => 50, 'amount' => 75000]);

        $response = $this->actingAs($rangeUser)->postJson(route('finance.work-order-print.preview'), [
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 5,
            'print_all_dockets' => 0,
            'docket_no' => '2',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 5,
            'docket_no' => '2',
        ]);
        $response->assertJsonPath('data.totals.count', 1);
        $response->assertJsonPath('data.work_orders.0.party_name', 'Somnath Forestry Contractors');
        $response->assertJsonPath('data.work_orders.0.extend_days', 5);
        $response->assertJsonPath('data.work_orders.0.total_amount', 75000);
        $response->assertJsonPath('data.work_orders.0.start_date', '01/08/2026');
        $response->assertJsonPath('data.work_orders.0.original_end_date', '10/08/2026');
        $response->assertJsonPath('data.work_orders.0.extended_end_date', '15/08/2026');
        $response->assertJsonPath('data.work_orders.0.work_order_no', 'WO/303');
    }

    public function test_print_endpoint_renders_printable_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $this->voucher($rangeUser, 304, ['entry_type' => 'Free Entry', 'party_name' => 'Gujarat State Seeds Agency', 'budget_code' => '2406-02-110-04', 'scheme' => 'Afforestation Project', 'docket_no' => 3],
            ['description' => 'Supply of Teak Seedlings', 'quantity' => 1500, 'unit' => 'Plants', 'rate' => 30, 'amount' => 45000]);

        $response = $this->actingAs($rangeUser)->get(route('finance.work-order-print.print', [
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 2,
            'docket_no' => '3',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('કાર્ય હુકમ (WORK ORDER)');
        $response->assertSee('Gujarat State Seeds Agency');
        $response->assertSee('2406-02-110-04');
        $response->assertSee('Supply of Teak Seedlings');
        $response->assertSee('45,000.00');
        $response->assertSee('WO/304');
        $response->assertSee('પરિક્ષેત્ર વન અધિકારી (RFO)');
    }

    public function test_nothing_is_invented_when_there_are_no_work_orders(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1, [], ['work_order_no' => null]); // voucher without a recorded work order

        $page = $this->actingAs($rangeUser)->get(route('finance.work-order-print.print', ['month' => 'Aug', 'print_all_dockets' => 1]));
        $page->assertSee('No work orders found');
        $page->assertSee('1 voucher(s) of this selection have no work order recorded');
        foreach (['Shree Ram Forest Labour', 'Compartment 42', 'DOC/2026', 'WO/RFO'] as $demo) {
            $page->assertDontSee($demo);
        }
        $index = $this->actingAs($rangeUser)->get(route('finance.work-order-print.index'));
        $index->assertDontSee('DOC/2026');
    }

    public function test_only_own_vouchers_of_the_month_and_dockets_and_missing_end_date(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $other = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1, ['docket_no' => 1], ['work_end_date' => null]);
        $this->voucher($rangeUser, 2, ['docket_no' => 2]);
        $this->voucher($rangeUser, 3, ['entry_month' => 'Jun']);
        $this->voucher($other, 1, ['party_name' => 'Somebody Else']);

        $all = $this->actingAs($rangeUser)->postJson(route('finance.work-order-print.preview'), ['month' => 'Aug', 'print_all_dockets' => 1, 'extend_days' => 3])->json('data');
        $this->assertSame(2, $all['totals']['count']);
        $this->assertSame('', $all['work_orders'][0]['extended_end_date']); // no recorded end date: never guessed
        $this->assertSame('13/08/2026', $all['work_orders'][1]['extended_end_date']);

        $one = $this->actingAs($rangeUser)->postJson(route('finance.work-order-print.preview'), ['month' => 'Aug', 'docket_no' => '2'])->json('data');
        $this->assertSame(1, $one['totals']['count']);
    }
}
