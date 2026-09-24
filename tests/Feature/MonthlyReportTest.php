<?php

namespace Tests\Feature;

use App\Models\BillAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_can_access_monthly_reports_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.monthly-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Generate and Download Monthly Reports:');
        $response->assertSee('Month:');
        $response->assertSee('Payment Mode:');
        $response->assertSee('Bill Type:');
        $response->assertSee('IFMS');
        $response->assertSee('SNA');
        $response->assertSee('એકથી વધુ સિલેક્ટ કરી શકો છો, તો તેનો ભેગો રિપોર્ટ ડાઉનલોડ થશે.');
        $response->assertSee('Form-53 Abstract');
        $response->assertSee('Form-53 New');
        $response->assertSee('Reconciliation');
        $response->assertSee('ePayment Report');
        $response->assertSee('Deduction Reports');
        $response->assertSee('Deduction Reports Yearly (વાર્ષિક)');
        $response->assertSee('GPF &amp; NPS Reports', false);
        $response->assertSee('Range Reports');
        $response->assertSee('Challan Report');
        $response->assertSee('Pay Slips');
    }

    public function test_range_user_cannot_access_division_monthly_reports_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.monthly-reports.index'));

        $response->assertStatus(403);
    }

    public function test_schemes_endpoint_returns_options_for_modes(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        // 1. IFMS
        $ifmsResponse = $this->actingAs($divisionUser)->getJson(route('division.monthly-reports.schemes', [
            'payment_mode' => 'IFMS',
        ]));
        $ifmsResponse->assertStatus(200);
        $ifmsResponse->assertJson([
            'success' => true,
            'payment_mode' => 'IFMS',
            'options' => ['Contingency', 'Simple Receipt'],
        ]);

        // 2. SNA
        $snaResponse = $this->actingAs($divisionUser)->getJson(route('division.monthly-reports.schemes', [
            'payment_mode' => 'SNA',
        ]));
        $snaResponse->assertStatus(200);
        $snaResponse->assertJson([
            'success' => true,
            'payment_mode' => 'SNA',
        ]);
        $snaResponse->assertJsonFragment(['2406- National Bamboo Mission']);
    }

    public function test_preview_endpoint_compiles_monthly_report_data(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        BillAdvice::create([
            'division_id' => $divisionUser->id,
            'bill_type' => 'Simple Receipt',
            'advice_no' => 'ADV-77',
            'advice_date' => '2026-04-15',
            'total_amount' => 25000.00,
            'total_bills_count' => 1,
            'selected_entries' => [
                [
                    'bill_register_no' => '99',
                    'order_outward_no' => 'વન/હસબ/૧૨૩૪/૨૦૨૬-૨૭',
                    'amount' => 25000.00,
                ],
            ],
            'status' => 'approved',
            'remarks' => 'Monthly passed advice',
        ]);

        $response = $this->actingAs($divisionUser)->postJson(route('division.monthly-reports.preview'), [
            'month' => 'Apr',
            'payment_mode' => 'IFMS',
            'bill_types' => ['Simple Receipt'],
            'report_type' => 'form_53_abstract',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Apr',
            'payment_mode' => 'IFMS',
            'report_type' => 'form_53_abstract',
        ]);
        $response->assertJsonPath('data.totals.count', 1);
        $response->assertJsonPath('data.bills.0.advice_no', 'ADV-77');
    }

    public function test_print_endpoint_renders_printable_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.monthly-reports.print', [
            'month' => 'Apr',
            'payment_mode' => 'IFMS',
            'report_type' => 'form_53_new',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('FORM NO. 53 (NEW)');
        $response->assertSee('મુખ્ય હિસાબનીશ (Head Accountant)');
        $response->assertSee('નાયબ વન સંરક્ષક (Dy. Conservator of Forests)');
    }
}
