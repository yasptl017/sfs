<?php

namespace Tests\Feature;

use App\Models\ChangeBillOrderNo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeBillOrderNoTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_change_bill_order_no_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.change-bill-order-no.index'));

        $response->assertStatus(200);
        $response->assertSee('Change Bill No and Order No:');
        $response->assertSee('Bill Type:');
        $response->assertSee('Advice No:');
        $response->assertSee('Advice Date:');
        $response->assertSee('Bill Register No.:');
        $response->assertSee('Order Outward No.:');
        $response->assertSee('Submit');
        $response->assertSee('Simple Receipt');
        $response->assertSee('Contingency');
        $response->assertSee('SNA');
    }

    public function test_range_user_cannot_access_change_bill_order_no_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.change-bill-order-no.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_store_and_update_bill_and_order_numbers(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->post(route('division.change-bill-order-no.store'), [
            'bill_type' => 'Simple Receipt',
            'advice_no' => '66',
            'advice_date' => '2026-09-18',
            'bill_register_no' => '74',
            'order_outward_no' => 'બ/હસબ/૩૮૮૩/૨૦૨૬-૨૭ ત',
            'remarks' => 'Updated outward sequence',
        ]);

        $response->assertRedirect(route('division.change-bill-order-no.index'));
        $response->assertSessionHas('status', 'Bill Register No. and Order Outward No. updated successfully.');

        $this->assertDatabaseHas('change_bill_order_numbers', [
            'division_id' => $divisionUser->id,
            'bill_type' => 'Simple Receipt',
            'advice_no' => '66',
            'bill_register_no' => '74',
            'order_outward_no' => 'બ/હસબ/૩૮૮૩/૨૦૨૬-૨૭ ત',
        ]);
    }

    public function test_details_endpoint_returns_json_data(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        ChangeBillOrderNo::create([
            'division_id' => $divisionUser->id,
            'bill_type' => 'Simple Receipt',
            'advice_no' => '66',
            'advice_date' => '2026-09-18',
            'bill_register_no' => '74',
            'order_outward_no' => 'બ/હસબ/૩૮૮૩/૨૦૨૬-૨૭ ત',
        ]);

        $response = $this->actingAs($divisionUser)->getJson(route('division.change-bill-order-no.details', [
            'bill_type' => 'Simple Receipt',
            'advice_no' => '66',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'advice_date' => '2026-09-18',
            'bill_register_no' => '74',
            'order_outward_no' => 'બ/હસબ/૩૮૮૩/૨૦૨૬-૨૭ ત',
            'is_existing' => true,
        ]);
    }

    public function test_division_user_can_delete_change_record(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $record = ChangeBillOrderNo::create([
            'division_id' => $divisionUser->id,
            'bill_type' => 'Contingency',
            'advice_no' => '99',
            'advice_date' => '2026-09-18',
            'bill_register_no' => '102',
            'order_outward_no' => 'બ/હસબ/૯૯/૨૦૨૬-૨૭ ત',
        ]);

        $response = $this->actingAs($divisionUser)->delete(route('division.change-bill-order-no.destroy', $record));

        $response->assertRedirect(route('division.change-bill-order-no.index'));
        $this->assertDatabaseMissing('change_bill_order_numbers', ['id' => $record->id]);
    }
}
