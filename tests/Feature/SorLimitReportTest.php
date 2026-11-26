<?php

namespace Tests\Feature;

use App\Models\BudgetCode;
use App\Models\RangeLocation;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SorLimitReportTest extends TestCase
{
    use RefreshDatabase;

    /** A SOR master line with two dated rates. */
    private function sor(string $code, float $oldRate, float $newRate): int
    {
        $id = DB::table('sor_items')->insertGetId(['financial_year_id' => CurrentFinancialYear::id(), 'tender_code' => 'SN01', 'divi_code' => $code, 'serial_number' => 1, 'particular' => 'Work '.$code, 'unit' => 'Nos']);
        DB::table('sor_rates')->insert([['sor_item_id' => $id, 'effective_from' => '2024-04-01', 'rate' => $oldRate, 'source_col' => 1], ['sor_item_id' => $id, 'effective_from' => '2026-04-01', 'rate' => $newRate, 'source_col' => 2]]);

        return $id;
    }

    private function voucher(User $range, int $serial, array $over = [], array $item = []): Voucher
    {
        $v = Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $serial,
            'party_name' => 'Party '.$serial, 'budget_code' => '2406-01-101-01', 'scheme' => 'State Plantation Scheme', 'entry_month' => 'Aug',
            'round' => 'Gir Round', 'beat' => 'Talala Beat', 'place' => 'Plot 55',
        ], $over));
        $v->items()->create(array_merge([
            'line_no' => 1, 'sor_code' => 'SN01/1', 'description' => 'Pit Digging', 'quantity' => 100, 'unit' => 'Pits', 'rate' => 12.50, 'amount' => 1250,
            'work_start_date' => '2026-08-01', 'raw_line' => '[]',
        ], $item));

        return $v;
    }

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

        $this->sor('SN01/1', 10, 12.5);
        $this->voucher($rangeUser, 1);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.sor-limit-report.filters'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('budget_codes.0.budget_code', '2406-02-110-03');
        $response->assertJsonPath('locations.Gir Round.Talala Beat.0', 'Plot 55');
        $response->assertJsonPath('sor_items.0.code', 'SN01/1');
        $response->assertJsonPath('sor_items.0.standard_rate', 12.5);
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

        $this->sor('SN01/1', 10, 12.5);
        $this->sor('SN01/2', 60, 65);
        $this->voucher($rangeUser, 501);                                                    // 12.50 vs 12.50 -> within
        $this->voucher($rangeUser, 502, [], ['sor_code' => 'SN01/2', 'rate' => 70, 'quantity' => 500, 'amount' => 35000]); // 70 > 65 -> exceeded

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
        $detailedResp->assertJsonPath('data.detailed_rows.0.sor_code', 'SN01/1');
        $detailedResp->assertJsonPath('data.detailed_rows.0.status', 'Within Limit');
        $detailedResp->assertJsonPath('data.detailed_rows.1.sor_code', 'SN01/2');
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
        $this->assertEquals('SN01/2', $violRows[0]['sor_code']);
        $this->assertEquals(65, $violRows[0]['sanctioned_rate']);
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

    public function test_sor_rate_in_force_on_the_work_date_is_used(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $this->sor('SN01/1', 10, 12.5);
        $this->voucher($rangeUser, 1, [], ['work_start_date' => '2025-06-01', 'rate' => 12.5]); // old rate 10 applied: 12.5 is too high
        $this->voucher($rangeUser, 2, [], ['work_start_date' => '2026-08-01', 'rate' => 12.5]); // new rate 12.5: fine

        $rows = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), ['report_mode' => 'detailed'])->json('data.detailed_rows');

        $this->assertEquals(10, $rows[0]['sanctioned_rate']);
        $this->assertTrue($rows[0]['is_rate_violation']);
        $this->assertEquals(12.5, $rows[1]['sanctioned_rate']);
        $this->assertFalse($rows[1]['is_violation']);
    }

    public function test_estimate_quantity_and_amount_limit_uses_the_years_cumulative_billing(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        DB::table('estimate_items')->insert(['financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $rangeUser->id, 'tender_code' => 'EN03', 'divi_code' => 'EN03/1', 'serial_number' => 1,
            'particular' => 'Fencing', 'unit' => 'Mtr', 'rate' => 10, 'quantity' => 150, 'amount' => 1500]);
        $this->voucher($rangeUser, 1, [], ['sor_code' => 'EN03/1', 'quantity' => 100, 'rate' => 10, 'amount' => 1000]);
        $this->voucher($rangeUser, 2, ['entry_month' => 'Sep'], ['sor_code' => 'EN03/1', 'quantity' => 100, 'rate' => 10, 'amount' => 1000]);

        // August alone is within 1,500, but the year's billing (2,000) exceeds the estimate.
        $d = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), ['month' => 'Aug', 'report_mode' => 'summary'])->json('data');
        $row = $d['summary_rows'][0];
        $this->assertEquals(1500, $row['sanctioned_limit']);
        $this->assertEquals(150, $row['sanctioned_qty']);
        $this->assertEquals(1000, $row['actual_amount']);
        $this->assertEquals(-500, $row['balance_amount']);
        $this->assertSame('Exceeded Limit', $row['status']);
    }

    public function test_items_without_a_reference_are_listed_as_not_in_rate_list_and_nothing_is_invented(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $other = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1, [], ['sor_code' => 'XX/9']);
        $this->voucher($other, 1, ['party_name' => 'Somebody Else']);

        $d = $this->actingAs($rangeUser)->postJson(route('finance.sor-limit-report.preview'), ['report_mode' => 'detailed'])->json('data');

        $this->assertCount(1, $d['detailed_rows']);
        $this->assertSame('Not in rate list', $d['detailed_rows'][0]['status']);
        $this->assertNull($d['detailed_rows'][0]['sanctioned_rate']);
        $this->assertNull($d['detailed_rows'][0]['sanctioned_limit']);
        $this->assertSame(1, $d['totals']['items_not_in_rate_list']);

        $page = $this->actingAs($rangeUser)->get(route('finance.sor-limit-report.print', ['report_mode' => 'detailed']));
        foreach (['Shree Ram Forest Labour', 'VCH-0001', 'Compartment No. 12', 'Girnar Forest Workers'] as $demo) {
            $page->assertDontSee($demo);
        }
        $empty = User::factory()->create(['role' => 'range']);
        $this->actingAs($empty)->postJson(route('finance.sor-limit-report.preview'), [])->assertJsonPath('data.totals.total_items_count', 0);
        $this->actingAs($empty)->get(route('finance.sor-limit-report.index'))->assertOk()->assertDontSee('SOR-01');
    }
}
