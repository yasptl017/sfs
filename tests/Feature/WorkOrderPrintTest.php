<?php

namespace Tests\Feature;

use App\Models\FreeEntry;
use App\Models\TenderEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderPrintTest extends TestCase
{
    use RefreshDatabase;

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

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 301,
            'data' => [
                'party_name' => 'Girnar Forest Workers Society',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/WO-101',
                'total_amount' => 60000.00,
            ]
        ]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.work-order-print.dockets', [
            'month' => 'Aug',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
        ]);
        $response->assertJsonFragment(['DOC/AUG/WO-101']);
    }

    public function test_preview_endpoint_calculates_extended_dates_and_amounts(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 302,
            'data' => [
                'party_name' => 'Somnath Forestry Contractors',
                'party_address' => 'Near Range Forest Depot, Junagadh Road',
                'budget_code' => '2406-01-101-01',
                'scheme' => 'Soil & Moisture Conservation Project',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/WO-102',
                'work_order_date' => '2026-08-01',
                'total_amount' => 75000.00,
                'items' => [
                    [
                        'description' => 'Trenching and Pit Digging',
                        'plot_area' => 'Plot No. 15 (5 Ha)',
                        'quantity' => 1500,
                        'unit' => 'Pits',
                        'rate' => 50,
                        'amount' => 75000.00,
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($rangeUser)->postJson(route('finance.work-order-print.preview'), [
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 5,
            'print_all_dockets' => 0,
            'docket_no' => 'DOC/AUG/WO-102',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 5,
            'docket_no' => 'DOC/AUG/WO-102',
        ]);
        $response->assertJsonPath('data.totals.count', 1);
        $response->assertJsonPath('data.work_orders.0.party_name', 'Somnath Forestry Contractors');
        $response->assertJsonPath('data.work_orders.0.extend_days', 5);
        $response->assertJsonPath('data.work_orders.0.total_amount', 75000);
    }

    public function test_print_endpoint_renders_printable_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        FreeEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 303,
            'data' => [
                'party_name' => 'Gujarat State Seeds Agency',
                'party_address' => 'Station Road, Gandhinagar',
                'budget_code' => '2406-02-110-04',
                'scheme' => 'Afforestation Project',
                'month' => 'Aug',
                'docket_no' => 'DOC/AUG/WO-103',
                'work_order_date' => '2026-08-05',
                'total_amount' => 45000.00,
                'items' => [
                    [
                        'description' => 'Supply of Teak Seedlings',
                        'plot_area' => 'Nursery Site B',
                        'quantity' => 1500,
                        'unit' => 'Plants',
                        'rate' => 30,
                        'amount' => 45000.00,
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($rangeUser)->get(route('finance.work-order-print.print', [
            'month' => 'Aug',
            'attachment_mode' => 'Separate',
            'extend_days' => 2,
            'docket_no' => 'DOC/AUG/WO-103',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('કાર્ય હુકમ (WORK ORDER)');
        $response->assertSee('Gujarat State Seeds Agency');
        $response->assertSee('2406-02-110-04');
        $response->assertSee('Supply of Teak Seedlings');
        $response->assertSee('45,000.00');
        $response->assertSee('પરિક્ષેત્ર વન અધિકારી (RFO)');
    }
}
