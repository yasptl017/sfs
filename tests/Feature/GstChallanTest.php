<?php

namespace Tests\Feature;

use App\Models\GstChallan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GstChallanTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_gst_challan_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.gst-challan.index'));

        $response->assertStatus(200);
        $response->assertSee('Update GST Challan');
        $response->assertSee('Bill Register No.');
        $response->assertSee('Advice No.');
        $response->assertSee('GST Amount');
        $response->assertSee('GST Challan No.');
    }

    public function test_range_user_cannot_access_gst_challan_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.gst-challan.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_store_gst_challan(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->post(route('division.gst-challan.store'), [
            'bill_register_no' => 'BR/2026/999',
            'advice_no' => 'ADV/2026/888',
            'gst_amount' => '4500.50',
            'gst_challan_no' => 'GST-TEST-12345',
        ]);

        $response->assertRedirect(route('division.gst-challan.index'));
        $response->assertSessionHas('status', 'GST Challan No. updated successfully.');

        $this->assertDatabaseHas('gst_challans', [
            'division_id' => $divisionUser->id,
            'bill_register_no' => 'BR/2026/999',
            'advice_no' => 'ADV/2026/888',
            'gst_challan_no' => 'GST-TEST-12345',
        ]);
    }

    public function test_details_endpoint_returns_json(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        GstChallan::create([
            'division_id' => $divisionUser->id,
            'bill_register_no' => 'BR/2026/ABC',
            'advice_no' => 'ADV/2026/XYZ',
            'gst_amount' => 2500.00,
            'gst_challan_no' => 'GST-ABC-XYZ',
        ]);

        $response = $this->actingAs($divisionUser)->getJson(route('division.gst-challan.details', [
            'bill_register_no' => 'BR/2026/ABC',
            'advice_no' => 'ADV/2026/XYZ',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'gst_amount' => 2500.00,
            'gst_challan_no' => 'GST-ABC-XYZ',
            'is_existing' => true,
        ]);
    }
}
