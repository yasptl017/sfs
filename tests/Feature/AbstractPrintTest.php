<?php

namespace Tests\Feature;

use App\Models\FreeEntry;
use App\Models\TenderEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractPrintTest extends TestCase
{
    use RefreshDatabase;

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

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 101,
            'data' => [
                'party_name' => 'Patel Earthmovers',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/001',
                'total_amount' => 50000.00,
            ]
        ]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.abstract-print.dockets', [
            'month' => 'Aug',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
        ]);
        $response->assertJsonFragment(['DOC/AUG/001']);
    }

    public function test_preview_endpoint_returns_compiled_abstract_data(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 102,
            'data' => [
                'party_name' => 'Shree Ram Construction',
                'budget_code' => '2406-01-101-01',
                'scheme' => 'Soil & Moisture Conservation',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/002',
                'total_amount' => 125000.00,
                'items' => [
                    [
                        'description' => 'Contour Trenching and Plantation',
                        'plot_area' => 'Comp. 45 - 25 Ha',
                        'start_date' => '2026-08-01',
                        'end_date' => '2026-08-15',
                        'quantity' => 25,
                        'unit' => 'Ha',
                        'rate' => 5000,
                        'amount' => 125000.00,
                    ]
                ],
                'deductions' => [
                    ['label' => 'GST TDS 2%', 'amount' => 2500.00],
                    ['label' => 'IT TDS 1%', 'amount' => 1250.00],
                ]
            ]
        ]);

        $response = $this->actingAs($rangeUser)->postJson(route('finance.abstract-print.preview'), [
            'month' => 'Aug',
            'docket_no' => 'DOC/AUG/002',
            'mode' => 'detailed',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
            'docket_no' => 'DOC/AUG/002',
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

        FreeEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 201,
            'data' => [
                'party_name' => 'Gujarat State Seeds Corp',
                'budget_code' => '2406-02-110-04',
                'scheme' => 'Afforestation Project',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/003',
                'total_amount' => 36000.00,
                'items' => [
                    [
                        'description' => 'Supply of Teak Seedlings',
                        'plot_area' => 'Nursery Site B',
                        'quantity' => 1200,
                        'rate' => 30,
                        'amount' => 36000.00,
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($rangeUser)->get(route('finance.abstract-print.print', [
            'month' => 'Aug',
            'docket_no' => 'DOC/AUG/003',
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
}
