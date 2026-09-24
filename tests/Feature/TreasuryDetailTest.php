<?php

namespace Tests\Feature;

use App\Models\TreasuryDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_treasury_details_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.treasury-details.index'));

        $response->assertStatus(200);
        $response->assertSee('Add Treasury Details');
        $response->assertSee('Bill No.:');
        $response->assertSee('Advice No.:');
        $response->assertSee('Advice Date');
        $response->assertSee('Bill Type');
        $response->assertSee('Bill Amount');
        $response->assertSee('Treasury Token No.');
        $response->assertSee('Treasury Voucher No.');
    }

    public function test_range_user_cannot_access_treasury_details_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.treasury-details.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_store_treasury_details(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->post(route('division.treasury-details.store'), [
            'bill_no' => 'BR/2026/099',
            'advice_no' => 'ADV/2026/099',
            'advice_date' => '2026-09-17',
            'bill_type' => 'Contingency',
            'bill_amount' => '78500.00',
            'treasury_name' => 'District Treasury Office, Gandhinagar',
            'token_no' => 'TOK/2026/999',
            'token_date' => '2026-09-17',
            'tv_no' => 'TV/2026/555',
            'tv_date' => '2026-09-18',
            'status' => 'Cleared',
            'remarks' => 'Bill cleared with voucher no 555',
        ]);

        $response->assertRedirect(route('division.treasury-details.index'));
        $response->assertSessionHas('status', 'Treasury details saved successfully.');

        $this->assertDatabaseHas('treasury_details', [
            'division_id' => $divisionUser->id,
            'bill_no' => 'BR/2026/099',
            'advice_no' => 'ADV/2026/099',
            'tv_no' => 'TV/2026/555',
            'status' => 'Cleared',
        ]);
    }

    public function test_info_endpoint_returns_json(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        TreasuryDetail::create([
            'division_id' => $divisionUser->id,
            'bill_no' => 'BR/2026/123',
            'advice_no' => 'ADV/2026/123',
            'advice_date' => '2026-09-10',
            'bill_type' => 'Contingency',
            'bill_amount' => 50000.00,
            'token_no' => 'TOK/123',
            'tv_no' => 'TV/123',
            'status' => 'Cleared',
        ]);

        $response = $this->actingAs($divisionUser)->getJson(route('division.treasury-details.info', [
            'bill_no' => 'BR/2026/123',
            'advice_no' => 'ADV/2026/123',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'bill_amount' => 50000.00,
            'token_no' => 'TOK/123',
            'tv_no' => 'TV/123',
            'status' => 'Cleared',
            'is_existing' => true,
        ]);
    }

    public function test_division_user_can_delete_treasury_detail(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $record = TreasuryDetail::create([
            'division_id' => $divisionUser->id,
            'bill_no' => 'BR/2026/DEL',
            'advice_no' => 'ADV/2026/DEL',
            'bill_amount' => 10000.00,
        ]);

        $response = $this->actingAs($divisionUser)->delete(route('division.treasury-details.destroy', $record));

        $response->assertRedirect(route('division.treasury-details.index'));
        $this->assertDatabaseMissing('treasury_details', ['id' => $record->id]);
    }
}
