<?php

namespace Tests\Feature;

use App\Models\RangeLocation;
use App\Models\TenderEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VavetarRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_user_can_access_vavetar_register_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        RangeLocation::create([
            'range_id' => $rangeUser->id,
            'round' => 'North Round',
            'beat' => 'Beat 1',
            'place' => 'Compartment No. 12',
            'hectares' => 10.00,
            'place_year' => '2026-27',
        ]);

        $response = $this->actingAs($rangeUser)->get(route('finance.vavetar-register.index'));

        $response->assertStatus(200);
        $response->assertSee('Vavetar Register');
        $response->assertSee('Round');
        $response->assertSee('Beat');
        $response->assertSee('Place');
        $response->assertSee('Area');
        $response->assertSee('Date Wise');
        $response->assertSee('Work Wise');
        $response->assertSee('Warning');
        $response->assertSee('Report will be generated on the basis of passed voucher entries.');
        $response->assertSee('If any correction is required, please inform us.');
        $response->assertSee('North Round');
    }

    public function test_division_user_cannot_access_vavetar_register_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.vavetar-register.index'));

        $response->assertStatus(403);
    }

    public function test_locations_endpoint_returns_hierarchy(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        RangeLocation::create([
            'range_id' => $rangeUser->id,
            'round' => 'Gir Round',
            'beat' => 'Talala Beat',
            'place' => 'Plot 55',
            'hectares' => 15.50,
            'place_year' => '2026-27',
        ]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.vavetar-register.locations'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('locations.Gir Round.Talala Beat.0.place', 'Plot 55');
        $response->assertJsonPath('locations.Gir Round.Talala Beat.0.hectares', '15.50');
    }

    public function test_preview_endpoint_returns_date_wise_and_work_wise_data(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        TenderEntry::create([
            'range_id' => $rangeUser->id,
            'serial_number' => 401,
            'data' => [
                'party_name' => 'Somnath Forest Labour Society',
                'round' => 'Gir Round',
                'beat' => 'Talala Beat',
                'place' => 'Plot 55',
                'total_amount' => 50000.00,
                'items' => [
                    [
                        'description' => 'Pit Digging Work for Teak Plantation',
                        'quantity' => 2000,
                        'unit' => 'Pits',
                        'rate' => 25,
                        'amount' => 50000.00,
                    ]
                ]
            ]
        ]);

        // 1. Date Wise
        $responseDateWise = $this->actingAs($rangeUser)->postJson(route('finance.vavetar-register.preview'), [
            'round' => 'Gir Round',
            'beat' => 'Talala Beat',
            'place' => 'Plot 55',
            'area' => '15.50 Ha',
            'report_type' => 'date_wise',
        ]);

        $responseDateWise->assertStatus(200);
        $responseDateWise->assertJson([
            'success' => true,
            'round' => 'Gir Round',
            'beat' => 'Talala Beat',
            'place' => 'Plot 55',
            'report_type' => 'date_wise',
        ]);
        $responseDateWise->assertJsonPath('data.total_expenditure', 50000);
        $responseDateWise->assertJsonPath('data.date_wise_rows.0.party_name', 'Somnath Forest Labour Society');

        // 2. Work Wise
        $responseWorkWise = $this->actingAs($rangeUser)->postJson(route('finance.vavetar-register.preview'), [
            'round' => 'Gir Round',
            'beat' => 'Talala Beat',
            'place' => 'Plot 55',
            'area' => '15.50 Ha',
            'report_type' => 'work_wise',
        ]);

        $responseWorkWise->assertStatus(200);
        $responseWorkWise->assertJson([
            'success' => true,
            'report_type' => 'work_wise',
        ]);
        $responseWorkWise->assertJsonPath('data.total_expenditure', 50000);
    }

    public function test_print_endpoint_renders_date_wise_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.vavetar-register.print', [
            'round' => 'North Round',
            'beat' => 'Beat 1',
            'place' => 'Compartment No. 12',
            'area' => '10.00 Ha',
            'report_type' => 'date_wise',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('વાવેતર રજીસ્ટર (PLANTATION EXPENDITURE REGISTER)');
        $response->assertSee('તારીખવાર (DATE WISE)');
        $response->assertSee('North Round');
        $response->assertSee('Compartment No. 12');
        $response->assertSee('વનરક્ષક (Beat Guard)');
        $response->assertSee('વનપાલ (Forester / Round Officer)');
        $response->assertSee('પરિક્ષેત્ર વન અધિકારી (RFO)');
    }

    public function test_print_endpoint_renders_work_wise_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.vavetar-register.print', [
            'round' => 'North Round',
            'beat' => 'Beat 1',
            'place' => 'Compartment No. 12',
            'area' => '10.00 Ha',
            'report_type' => 'work_wise',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('વાવેતર રજીસ્ટર (PLANTATION EXPENDITURE REGISTER)');
        $response->assertSee('કામગીરીવાર (WORK WISE)');
    }
}
