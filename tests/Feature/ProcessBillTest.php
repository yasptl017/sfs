<?php

namespace Tests\Feature;

use App\Models\BillAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_process_bill_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.process-bill.index'));

        $response->assertStatus(200);
        $response->assertSee('Process Bill (Advice)');
        $response->assertSee('Select Bill Type');
        $response->assertSee('Contingency');
        $response->assertSee('Simple Receipt');
        $response->assertSee('SNA');
        $response->assertSee('IFMS (Simple Receipt + Contingency)');
        $response->assertSee('Select Received');
        $response->assertSee('Show All');
        $response->assertSee('Show Selected');
        $response->assertSee('Show Red');
        $response->assertSee('Deselect All');
        $response->assertSee('Verify and Process');
        $response->assertSee('Total of Selected');
    }

    public function test_range_user_cannot_access_process_bill_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.process-bill.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_process_and_generate_advice(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->post(route('division.process-bill.store'), [
            'advice_no' => 'ADV/2026/001',
            'advice_date' => '2026-09-17',
            'bill_type' => 'Contingency',
            'total_amount' => '49000.00',
            'selected_entries' => [
                ['id' => 'SAMPLE-1', 'sr_no' => 21, 'amount' => 24500.00],
                ['id' => 'SAMPLE-2', 'sr_no' => 22, 'amount' => 24500.00],
            ],
            'remarks' => 'Batch processing approval',
        ]);

        $response->assertRedirect(route('division.process-bill.index', ['bill_type' => 'Contingency']));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('bill_advices', [
            'division_id' => $divisionUser->id,
            'advice_no' => 'ADV/2026/001',
            'bill_type' => 'Contingency',
            'total_bills_count' => 2,
        ]);
    }
}
