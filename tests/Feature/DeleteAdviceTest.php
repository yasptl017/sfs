<?php

namespace Tests\Feature;

use App\Models\BillAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAdviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_delete_advice_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.delete-advice.index'));

        $response->assertStatus(200);
        $response->assertSee('Delete Advice');
        $response->assertSee('Advice. No.');
        $response->assertSee('Active Advice Batches');
    }

    public function test_range_user_cannot_access_delete_advice_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.delete-advice.index'));

        $response->assertStatus(403);
    }

    public function test_details_endpoint_returns_json(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        BillAdvice::create([
            'division_id' => $divisionUser->id,
            'advice_no' => 'ADV/TEST/041',
            'advice_date' => '2026-09-17',
            'bill_type' => 'Contingency',
            'total_bills_count' => 4,
            'total_amount' => 60000.00,
            'selected_entries' => [['id' => 1, 'sr_no' => 1, 'amount' => 15000]],
            'status' => 'Processed',
        ]);

        $response = $this->actingAs($divisionUser)->getJson(route('division.delete-advice.details', [
            'advice_no' => 'ADV/TEST/041',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'exists' => true,
            'advice_no' => 'ADV/TEST/041',
            'total_amount' => 60000.00,
        ]);
    }

    public function test_division_user_can_delete_advice(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $advice = BillAdvice::create([
            'division_id' => $divisionUser->id,
            'advice_no' => 'ADV/DEL/041',
            'advice_date' => '2026-09-17',
            'bill_type' => 'Contingency',
            'total_bills_count' => 2,
            'total_amount' => 25000.00,
            'selected_entries' => [['id' => 1, 'sr_no' => 1, 'amount' => 12500]],
            'status' => 'Processed',
        ]);

        $response = $this->actingAs($divisionUser)->delete(route('division.delete-advice.destroy'), [
            'advice_no' => 'ADV/DEL/041',
        ]);

        $response->assertRedirect(route('division.delete-advice.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('bill_advices', [
            'id' => $advice->id,
            'advice_no' => 'ADV/DEL/041',
        ]);
    }

    public function test_division_user_can_delete_advice_model_directly(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $advice = BillAdvice::create([
            'division_id' => $divisionUser->id,
            'advice_no' => 'ADV/DIRECT/041',
            'advice_date' => '2026-09-17',
            'bill_type' => 'Contingency',
            'total_bills_count' => 1,
            'total_amount' => 15000.00,
            'selected_entries' => [['id' => 1, 'sr_no' => 1, 'amount' => 15000]],
            'status' => 'Processed',
        ]);

        $response = $this->actingAs($divisionUser)->delete(route('division.delete-advice.destroy-model', $advice));

        $response->assertRedirect(route('division.delete-advice.index'));
        $this->assertDatabaseMissing('bill_advices', ['id' => $advice->id]);
    }
}
