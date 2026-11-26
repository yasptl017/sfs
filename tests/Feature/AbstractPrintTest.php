<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractPrintTest extends TestCase
{
    use RefreshDatabase;

    private function voucher(User $range, int $serial, array $over = []): Voucher
    {
        return Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $serial,
            'party_name' => 'Party '.$serial, 'budget_code' => 'A3', 'scheme' => '02- Divisional', 'entry_month' => 'Aug', 'docket_no' => 1,
            'payable_total_amount' => 10000, 'total_deduction' => 200, 'net_amount' => 9800,
        ], $over));
    }

    public function test_range_user_can_access_abstract_print_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.abstract-print.index'));

        $response->assertStatus(200);
        $response->assertSee('Abstract Print:');
        $response->assertSee('Month');
        $response->assertSee('Docket No.');
        $response->assertSee('Print (with Rate and Quantity)');
        $response->assertSee('To print, the detailed Abstract with plot area, Rate, Quantity and work dates, then click above button');
        $response->assertSee('Print');
        $response->assertSee('Aug');
    }

    public function test_division_user_cannot_access_abstract_print_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.abstract-print.index'));

        $response->assertStatus(403);
    }

    public function test_dockets_endpoint_returns_json(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $this->voucher($rangeUser, 101, ['docket_no' => 7]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.abstract-print.dockets', [
            'month' => 'Aug',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
        ]);
        $response->assertJsonPath('dockets', ['7']);
    }

    public function test_preview_endpoint_returns_compiled_abstract_data(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $v = $this->voucher($rangeUser, 102, ['party_name' => 'Shree Ram Construction', 'budget_code' => '2406-01-101-01', 'scheme' => 'Soil & Moisture Conservation',
            'docket_no' => 2, 'payable_total_amount' => 125000, 'total_deduction' => 3750, 'net_amount' => 121250, 'tds' => 1250, 'deduction_sgst' => 2500]);
        $v->items()->create(['line_no' => 1, 'description' => 'Contour Trenching and Plantation', 'quantity' => 25, 'unit' => 'Ha', 'rate' => 5000, 'amount' => 125000, 'raw_line' => '[]']);
        $this->voucher($rangeUser, 103, ['docket_no' => 3, 'payable_total_amount' => 999]); // other docket: must not count

        $response = $this->actingAs($rangeUser)->postJson(route('finance.abstract-print.preview'), [
            'month' => 'Aug',
            'docket_no' => '2',
            'mode' => 'detailed',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
            'docket_no' => '2',
            'mode' => 'detailed',
        ]);
        $response->assertJsonPath('data.totals.count', 1);
        $response->assertJsonPath('data.totals.gross_amount', 125000);
        $response->assertJsonPath('data.totals.total_deductions', 3750);
        $response->assertJsonPath('data.totals.net_amount', 121250);
    }

    public function test_print_endpoint_renders_detailed_view(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $v = $this->voucher($rangeUser, 201, ['entry_type' => 'Free Entry', 'party_name' => 'Gujarat State Seeds Corp', 'budget_code' => '2406-02-110-04',
            'scheme' => 'Afforestation Project', 'docket_no' => 3, 'place' => 'Nursery Site B', 'payable_total_amount' => 36000, 'total_deduction' => 0, 'net_amount' => 36000]);
        $v->items()->create(['line_no' => 1, 'description' => 'Supply of Teak Seedlings', 'quantity' => 1200, 'unit' => 'No.', 'rate' => 30, 'amount' => 36000, 'raw_line' => '[]']);

        $response = $this->actingAs($rangeUser)->get(route('finance.abstract-print.print', [
            'month' => 'Aug',
            'docket_no' => '3',
            'mode' => 'detailed',
        ]));

        $response->assertStatus(200);
        $response->assertSee('MONTHLY VOUCHER ABSTRACT');
        $response->assertSee('Gujarat State Seeds Corp');
        $response->assertSee('2406-02-110-04');
        $response->assertSee('Supply of Teak Seedlings');
        $response->assertSee('Nursery Site B');
        $response->assertSee('36,000.00');
    }

    public function test_print_endpoint_renders_summary_view(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.abstract-print.print', [
            'month' => 'Aug',
            'mode' => 'summary',
        ]));

        $response->assertStatus(200);
        $response->assertSee('MONTHLY VOUCHER ABSTRACT');
        $response->assertSee('Summary Abstract');
    }

    public function test_a_month_without_vouchers_prints_a_notice_and_no_invented_data(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $page = $this->actingAs($rangeUser)->get(route('finance.abstract-print.print', ['month' => 'Aug', 'mode' => 'detailed']));
        $page->assertSee('nothing to print');
        foreach (['Shree Ram Forest Labour', 'Compartment 42', 'DOC/2026'] as $demo) {
            $page->assertDontSee($demo);
        }
        $json = $this->actingAs($rangeUser)->postJson(route('finance.abstract-print.preview'), ['month' => 'Aug'])->json('data.totals');
        $this->assertSame(0, $json['count']);
        $this->assertEquals(0, $json['net_amount']);
    }

    public function test_only_the_ranges_own_vouchers_of_the_month_are_included_and_recent_list_is_real(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $other = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1);
        $this->voucher($rangeUser, 2, ['entry_month' => 'Jun', 'docket_no' => 9]);
        $this->voucher($other, 1, ['party_name' => 'Somebody Else']);

        $t = $this->actingAs($rangeUser)->postJson(route('finance.abstract-print.preview'), ['month' => 'Aug'])->json('data');
        $this->assertSame(1, $t['totals']['count']);
        $this->assertSame('Party 1', $t['entries'][0]['party_name']);
        $this->assertEquals(9800, $t['totals']['net_amount']);

        $index = $this->actingAs($rangeUser)->get(route('finance.abstract-print.index'));
        $index->assertSee('Jun')->assertDontSee('DOC/2026')->assertDontSee('Somebody Else');
    }
}
