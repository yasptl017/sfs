<?php

namespace Tests\Feature;

use App\Models\BillAdviceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillAdviceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_bill_advice_reports_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.bill-advice-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Generate Bill Reports');
        $response->assertSee('Bill Register No:');
        $response->assertSee('Advice No:');
        $response->assertSee('Tharav Description');
        $response->assertSee('GST Report');
        $response->assertSee('Bill Reports');
        $response->assertSee('Deduction Reports');
        $response->assertSee('Reports for Ranges');
    }

    public function test_range_user_cannot_access_bill_advice_reports_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.bill-advice-reports.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_generate_bill_report(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->post(route('division.bill-advice-reports.generate'), [
            'bill_register_no' => 'BR/2026/042',
            'advice_no' => 'ADV/2026/042',
            'report_type' => 'bill_report',
            'tharav_descriptions' => "ઠરાવ ક્રમાંક: ૧૨૩/૨૦૨૬\nઠરાવ ક્રમાંક: ૪૫૬/૨૦૨૬",
        ]);

        $response->assertRedirect(route('division.bill-advice-reports.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('bill_advice_reports', [
            'division_id' => $divisionUser->id,
            'bill_register_no' => 'BR/2026/042',
            'advice_no' => 'ADV/2026/042',
            'report_type' => 'bill_report',
        ]);
    }

    public function test_division_user_can_generate_gst_report_via_json(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->postJson(route('division.bill-advice-reports.generate'), [
            'bill_register_no' => '42',
            'advice_no' => '42',
            'report_type' => 'gst_report',
            'tharav_descriptions' => ['ઠરાવ ક્રમાંક ૧'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_details_endpoint_returns_json(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->getJson(route('division.bill-advice-reports.details', [
            'bill_register_no' => '42',
            'advice_no' => '42',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'entries_count',
            'total_amount',
            'gst_amount',
            'deductions_total',
            'net_amount',
            'entries',
        ]);
    }

    public function test_division_user_can_view_printable_report(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $report = BillAdviceReport::create([
            'division_id' => $divisionUser->id,
            'bill_register_no' => '42',
            'advice_no' => '42',
            'report_type' => 'bill_report',
            'tharav_descriptions' => json_encode(['ઠરાવ ૧', 'ઠરાવ ૨']),
            'report_data' => [
                'bill_register_no' => '42',
                'advice_no' => '42',
                'division_name' => 'Forest Division Office',
                'entries' => [],
                'totals' => ['gross_amount' => 1000, 'total_deductions' => 100, 'net_amount' => 900],
            ],
        ]);

        $response = $this->actingAs($divisionUser)->get(route('division.bill-advice-reports.show', $report));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT FOREST DEPARTMENT');
        $response->assertSee('Bill Register No:');
    }
}
