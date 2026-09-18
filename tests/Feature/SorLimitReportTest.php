<?php

namespace Tests\Feature;

use App\Models\BudgetCode;
use App\Models\RangeLocation;
use App\Models\TenderEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SorLimitReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_user_can_access_sor_limit_report_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range', 'name' => 'Gir Gadhada Range']);

        BudgetCode::create([
            'budget_code' => '2406-01-101-01',
            'scheme' => 'State Plantation Scheme',
            'model' => 'Model A',
            'scheme_year' => '2026-27',
        ]);

        RangeLocation::create([
            'range_id' => $rangeUser->id,
            'round' => 'Babarkot Round',
            'beat' => 'Jafarabad Beat',
            'place' => 'Plantation Plot 10',
            'hectares' => 20.00,
            'place_year' => '2026-27',
        ]);

        $response = $this->actingAs($rangeUser)->get(route('finance.sor-limit-report.index'));

        $response->assertStatus(200);
        $response->assertSee('SOR Limit Report');
        $response->assertSee('Schedule of Rates (SOR) Limit Verification');
        $response->assertSee('Budget Code / Scheme');
        $response->assertSee('Month (માસ)');
        $response->assertSee('Fin. Year (વર્ષ)');
        $response->assertSee('Round (રાઉન્ડ)');
        $response->assertSee('Beat (બીટ)');
        $response->assertSee('Plantation Site / Place');
        $response->assertSee('SOR Item / Operation');
        $response->assertSee('Detailed Voucher');
        $response->assertSee('Consolidated SOR Code Summary');
        $response->assertSee('Limit Excess');
        $response->assertSee('Sanctioned Limit');
        $response->assertSee('Billed Expenditure');
        $response->assertSee('Net Savings / Balance');
        $response->assertSee('Compliance Rate');
    }

    public function test_division_user_cannot_access_sor_limit_report_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.sor-limit-report.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_sor_limit_report_page(): void
    {
        $response = $this->get(route('finance.sor-limit-report.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_filters_endpoint_returns_budget_codes_locations_and_sor_items(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        BudgetCode::create([
            'budget_code' => '2406-02-110-03',
            'scheme' => 'Soil & Moisture Conservation',
            'model' => 'Model SMC',
            'scheme_year' => '2026-27',
        ]);

        RangeLocation::create([
            'range_id' => $rangeUser->id,
            'round' => 'Gir Round',
            'beat' => 'Talala Beat',
            'place' => 'Plot 55',
            'hectares' => 15.50,
            'place_year' => '2026-27',
        ]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.sor-limit-report.filters'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('budget_codes.0.budget_code', '2406-02-110-03');
        $response->assertJsonPath('locations.Gir Round.Talala Beat.0', 'Plot 55');
        $response->assertJsonPath('sor_items.0.code', 'SOR-01');
    }

    public function test_preview_endpoint_returns_detailed_summary_and_violations_data(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        BudgetCode::create([
            'budget_code' => '2406-01-101-01',
            'scheme' => 'State Plantation Scheme',
            'model' => 'Model A',
            'scheme_year' => '2026-27',
        ]);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 501,
            'data' => [
                'data_entry_month' => 'Aug',
                'budget_code' => '2406-01-101-01',
                'round' => 'Gir Round',
                'beat' => 'Talala Beat',
                'place' => 'Plot 55',
                'party_name' => 'Somnath Forest Labour Society',
                'items' => [
                    [
                        'sor_code' => 'SOR-01',
                        'work_description' => 'Pit Digging 30x30x30 cm',
                        'unit' => 'Pits',
                        'rate' => 12.50,
                        'quantity' => 1000,
                        'sanctioned_quantity' => 1200,
                        'amount' => 12500.00,
                    ],
                    [
                        'sor_code' => 'SOR-02',
                        'work_description' => 'Barbed Wire Fencing',
                        'unit' => 'Mtr',
                        'rate' => 70.00, // Exceeds standard rate of 65.00
                        'quantity' => 500,
                        'sanctioned_quantity' => 500,
                        'amount' => 35000.00,
                    ]
                ]
            ]
        ]);

        // 1. Detailed Mode
        $detailedResp = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'report_mode' => 'detailed',
        ]);

        $detailedResp->assertStatus(200);
        $detailedResp->assertJson([
            'success' => true,
            'report_mode' => 'detailed',
        ]);
        $detailedResp->assertJsonPath('data.detailed_rows.0.sor_code', 'SOR-01');
        $detailedResp->assertJsonPath('data.detailed_rows.0.status', 'Within Limit');
        $detailedResp->assertJsonPath('data.detailed_rows.1.sor_code', 'SOR-02');
        $detailedResp->assertJsonPath('data.detailed_rows.1.status', 'Exceeded Limit');
        $detailedResp->assertJsonPath('data.detailed_rows.1.is_violation', true);

        // 2. Summary Mode
        $summaryResp = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'report_mode' => 'summary',
        ]);

        $summaryResp->assertStatus(200);
        $summaryResp->assertJson([
            'success' => true,
            'report_mode' => 'summary',
        ]);
        $this->assertNotEmpty($summaryResp->json('data.summary_rows'));

        // 3. Violations Mode
        $violResp = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'report_mode' => 'violations',
        ]);

        $violResp->assertStatus(200);
        $violResp->assertJson([
            'success' => true,
            'report_mode' => 'violations',
        ]);
        $violRows = $violResp->json('data.detailed_rows');
        $this->assertCount(1, $violRows);
        $this->assertEquals('SOR-02', $violRows[0]['sor_code']);
    }

    public function test_print_endpoint_renders_printable_report_with_office_profile(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range', 'name' => 'Gir North Range']);
        $profile = $rangeUser->getOrCreateOfficeProfile();
        $profile->update([
            'office_name' => 'Gir North Range Forest Office, Junagadh',
            'office_name_gujarati' => 'ગીર ઉત્તર પરિક્ષેત્ર વન કચેરી, જુનાગઢ',
            'officer_name' => 'R. B. Patel, GFS',
            'officer_designation' => 'Range Forest Officer',
        ]);

        // Detailed Print
        $response = $this->actingAs($rangeUser)->get(route('finance.sor-limit-report.print', [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'year' => '2026-27',
            'report_mode' => 'detailed',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('Gir North Range Forest Office, Junagadh');
        $response->assertSee('ગીર ઉત્તર પરિક્ષેત્ર વન કચેરી, જુનાગઢ');
        $response->assertSee('એસ.ઓ.આર. લિમિટ ચકાસણી અને ખર્ચ મોનિટરિંગ રિપોર્ટ');
        $response->assertSee('વનરક્ષક / વનપાલ (Beat Guard / Forester)');
        $response->assertSee('એકાઉન્ટન્ટ / કેશિયર (Accountant / Cashier)');
        $response->assertSee('R. B. Patel, GFS');

        // Summary Print
        $summaryPrint = $this->actingAs($rangeUser)->get(route('finance.sor-limit-report.print', [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'year' => '2026-27',
            'report_mode' => 'summary',
        ]));

        $summaryPrint->assertStatus(200);
        $summaryPrint->assertSee('Activity / SOR Work Description');
        $summaryPrint->assertSee('Sanctioned Limit (₹)');
        $summaryPrint->assertSee('Billed Amount (₹)');
    }

    public function test_export_endpoint_returns_csv_download(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range', 'name' => 'Una Range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.sor-limit-report.export', [
            'budget_code' => '2406-01-101-01',
            'month' => 'Aug',
            'year' => '2026-27',
            'report_mode' => 'detailed',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('GUJARAT FOREST DEPARTMENT - SOR LIMIT REPORT', $response->getContent());
        $this->assertStringContainsString('SOR Sanctioned Rate (Rs)', $response->getContent());
    }
}
