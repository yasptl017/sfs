<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_summary_reports_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.summary-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Generate and Download Summary Reports:');
        $response->assertSee('1. Abstract of Summary Report');
        $response->assertSee('2. Summary (Merged with Salary)');
        $response->assertSee('3. Summary (Only LC - Work)');
    }

    public function test_range_user_cannot_access_division_summary_reports(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.summary-reports.index'));

        $response->assertStatus(403);
    }

    public function test_preview_endpoint_returns_json_for_all_summary_types(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        // 1. Summary Abstract
        $res1 = $this->actingAs($divisionUser)->postJson(route('division.summary-reports.preview'), [
            'month' => 'Jul',
            'payment_mode' => 'ALL',
            'report_type' => 'summary_abstract',
        ]);
        $res1->assertStatus(200);
        $res1->assertJson(['success' => true, 'report_type' => 'summary_abstract']);
        $res1->assertJsonStructure([
            'data' => [
                'division_name',
                'month',
                'sections',
                'grand_totals' => ['sanctioned', 'released', 'total_exp', 'rem_req'],
            ],
        ]);

        // 2. Merged Salary & W-LC
        $res2 = $this->actingAs($divisionUser)->postJson(route('division.summary-reports.preview'), [
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
            'report_type' => 'summary_merged_salary',
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['success' => true, 'report_type' => 'summary_merged_salary']);
        $res2->assertJsonStructure([
            'data' => [
                'division_name',
                'groups',
            ],
        ]);

        // 3. Only L-C Model
        $res3 = $this->actingAs($divisionUser)->postJson(route('division.summary-reports.preview'), [
            'month' => 'Jul',
            'payment_mode' => 'ALL',
            'report_type' => 'summary_only_lc',
        ]);
        $res3->assertStatus(200);
        $res3->assertJson(['success' => true, 'report_type' => 'summary_only_lc']);
    }

    public function test_print_endpoint_renders_printable_summary_sheet(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.summary-reports.print', [
            'month' => 'Jul',
            'payment_mode' => 'ALL',
            'report_type' => 'summary_abstract',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('Abstract of SUMMARY of the month');
        $response->assertSee('મુખ્ય હિસાબનીશ');
        $response->assertSee('નાયબ વન સંરક્ષક');
    }
}
