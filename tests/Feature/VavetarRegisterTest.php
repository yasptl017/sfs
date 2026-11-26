<?php

namespace Tests\Feature;

use App\Models\RangeLocation;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VavetarRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function voucher(User $range, int $serial, array $over = [], array $item = []): Voucher
    {
        $v = Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $serial,
            'party_name' => 'Party '.$serial, 'budget_code' => 'A3', 'scheme' => '02- Divisional', 'round' => 'Gir Round', 'beat' => 'Talala Beat', 'place' => 'Plot 55',
        ], $over));
        $v->items()->create(array_merge([
            'line_no' => 1, 'description' => 'Pit digging', 'quantity' => 10, 'unit' => 'Pits', 'rate' => 100, 'amount' => 1000, 'work_start_date' => '2026-08-01', 'raw_line' => '[]',
        ], $item));

        return $v;
    }

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

        $this->voucher($rangeUser, 401, ['party_name' => 'Somnath Forest Labour Society'], ['description' => 'Pit Digging Work for Teak Plantation', 'quantity' => 2000, 'unit' => 'Pits', 'rate' => 25, 'amount' => 50000]);

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

    public function test_register_lists_only_real_vouchers_with_running_total_and_work_wise_groups(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $other = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1, [], ['amount' => 1000, 'work_start_date' => '2026-08-10']);
        $this->voucher($rangeUser, 2, [], ['amount' => 500, 'work_start_date' => '2026-08-02']);
        $this->voucher($rangeUser, 3, ['place' => 'Plot 99']);                              // another place
        $this->voucher($rangeUser, 4, ['rejected_reason' => 'wrong'], ['amount' => 7777]);   // rejected: not expenditure
        $this->voucher($other, 1, ['party_name' => 'Somebody Else']);

        $d = $this->actingAs($rangeUser)->postJson(route('finance.vavetar-register.preview'), ['round' => 'Gir Round', 'beat' => 'Talala Beat', 'place' => 'Plot 55', 'report_type' => 'date_wise'])->json('data');

        $this->assertEquals(1500, $d['total_expenditure']);
        $this->assertSame(2, $d['total_vouchers']);
        $this->assertSame(['02/08/2026', '10/08/2026'], array_column($d['date_wise_rows'], 'date'));
        $this->assertEquals([500, 1500], array_column($d['date_wise_rows'], 'cumulative_amount'));
        $this->assertCount(1, $d['work_wise_groups']);
        $this->assertSame('Pit digging', $d['work_wise_groups'][0]['category']);
        $this->assertEquals(1500, $d['work_wise_groups'][0]['total_amount']);
    }

    public function test_nothing_is_invented_for_a_place_without_vouchers(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $json = $this->actingAs($rangeUser)->getJson(route('finance.vavetar-register.locations'))->json('locations');
        $this->assertSame([], $json);
        $page = $this->actingAs($rangeUser)->get(route('finance.vavetar-register.print', ['round' => 'X', 'beat' => 'Y', 'place' => 'Z', 'report_type' => 'date_wise']));
        foreach (['Shree Ram Forest Labour', 'Compartment No. 12', 'VCH-0001', 'Girnar Forest Workers'] as $demo) {
            $page->assertDontSee($demo);
        }
        $this->actingAs($rangeUser)->get(route('finance.vavetar-register.index'))->assertDontSee('North Round');
    }

    public function test_places_used_by_vouchers_are_reachable_even_when_not_in_the_location_list(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 1, ['round' => 'Round A', 'beat' => 'Beat A', 'place' => 'Unlisted Plot ']);

        $loc = $this->actingAs($rangeUser)->getJson(route('finance.vavetar-register.locations'))->json('locations');
        $this->assertSame('Unlisted Plot', $loc['Round A']['Beat A'][0]['place']);
        $d = $this->actingAs($rangeUser)->postJson(route('finance.vavetar-register.preview'), ['round' => 'Round A', 'beat' => 'Beat A', 'place' => 'Unlisted Plot'])->json('data');
        $this->assertSame(1, $d['total_vouchers']);
    }
}
