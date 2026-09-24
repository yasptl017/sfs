<?php

namespace Tests\Feature;

use App\Models\TenderEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_user_can_access_voucher_print_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.voucher-print.index'));

        $response->assertStatus(200);
        $response->assertSee('Voucher Print:');
        $response->assertSee('Which entry to print ?');
        $response->assertSee('Serial numbers');
        $response->assertSee('seperated by comma / dash');
        $response->assertSee('With Work Order Numbers');
        $response->assertSee('Fit to page');
        $response->assertSee('Print');
        $response->assertSee('Tender Entry');
        $response->assertSee('Free Entry');
        $response->assertSee('D.Wagers Salary');
        $response->assertSee('D.Wagers Arrears');
        $response->assertSee('WL Bene. Entry');
    }

    public function test_division_user_cannot_access_voucher_print_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.voucher-print.index'));

        $response->assertStatus(403);
    }

    public function test_entries_endpoint_returns_json_for_selected_type(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 1,
            'data' => [
                'party_name' => 'Shree Ram Construction',
                'budget_code' => '2406-01-101-01',
                'docket_no' => 'DOC/2026/001',
                'total_amount' => 45000.00,
            ]
        ]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.voucher-print.entries', [
            'entry_type' => 'Tender Entry',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'entry_type' => 'Tender Entry',
        ]);
        $response->assertJsonFragment([
            'serial_number' => 1,
            'party_name' => 'Shree Ram Construction',
        ]);
    }

    public function test_preview_endpoint_returns_compiled_vouchers(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 5,
            'data' => [
                'party_name' => 'Gujarat Agro Forestry',
                'budget_code' => '2406-01-101-02',
                'total_amount' => 30000.00,
                'items' => [
                    [
                        'item_code' => 1,
                        'item_description' => 'Plantation digging',
                        'quantity' => 100,
                        'unit' => 'No.',
                        'rate' => 300,
                        'amount' => 30000,
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($rangeUser)->postJson(route('finance.voucher-print.preview'), [
            'entry_type' => 'Tender Entry',
            'serial_numbers' => '5',
            'with_work_order' => 1,
            'fit_to_page' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment([
            'serial_number' => 5,
            'party_name' => 'Gujarat Agro Forestry',
        ]);
    }

    public function test_print_endpoint_renders_printable_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.voucher-print.print', [
            'entry_type' => 'Tender Entry',
            'serial_numbers' => '1-3',
            'with_work_order' => 1,
            'fit_to_page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT FOREST DEPARTMENT');
        $response->assertSee('Tender Entry VOUCHER (FORM NO. 35)');
        $response->assertSee('Deductions');
        $response->assertSee('Net Payable Amount');
    }
}
