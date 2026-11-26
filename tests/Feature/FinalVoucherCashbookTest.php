<?php

namespace Tests\Feature;

use App\Models\LcEntry;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalVoucherCashbookTest extends TestCase
{
    use RefreshDatabase;

    private function voucher(User $range, array $over = []): Voucher
    {
        static $n = 0;
        $n++;

        return Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $n, 'party_name' => 'Party '.$n,
            'budget_code' => 'A3', 'scheme' => 'Divisional', 'payment_mode' => 'IFMS', 'ifms_bill_type' => 'Simple Receipt', 'entry_month' => 'Jun',
            'payable_total_amount' => 1000, 'total_deduction' => 20, 'net_amount' => 980,
            'treasury_status' => 'Approved', 'treasury_approval_date' => '2026-06-10', 'advice_no' => 1, 'advice_date' => '2026-06-08',
        ], $over));
    }

    private function division(): array
    {
        $d = User::factory()->create(['role' => 'division']);

        return [$d, User::factory()->create(['role' => 'range', 'division_id' => $d->id, 'name' => 'Idar'])];
    }

    public function test_division_user_can_access_final_voucher_cashbook_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.final-voucher-cashbook.index'));

        $response->assertStatus(200);
        $response->assertSee('Assign Final Voucher Nos and Form No. 35 and Cashbook:');
        $response->assertSee('Payment Mode / Scheme:');
        $response->assertSee('Merge schemes for Form 35 &amp; Cashbook', false);
        $response->assertSee('Manage merged groups');
        $response->assertSee('Month:');
        $response->assertSee('Scale');
        $response->assertSee('Page Size');
        $response->assertSee('Assign Final Voucher Nos');
        $response->assertSee('Sorted');
        $response->assertSee('Print Form No. 35 &amp; Vouchers Sorted', false);
        $response->assertSee('Vouchers sorted as per Form 35');
        $response->assertSee('Cash Book Reports');
        $response->assertSee('Prepare Cash Book');
        $response->assertSee('Download Credit Part');
        $response->assertSee('Download Debit Part');
        $response->assertSee('Range');
        $response->assertSee('Form-35 for range');
        $response->assertSee('Cashbook for range');
        $response->assertSee('આ રિપોર્ટ નવો હોવાથી ચેક કરવો.');
    }

    public function test_range_user_cannot_access_division_final_voucher_cashbook_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('division.final-voucher-cashbook.index'));

        $response->assertStatus(403);
    }

    public function test_division_user_can_assign_final_vouchers(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->postJson(route('division.final-voucher-cashbook.assign'), [
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
            'scale' => 'full_compact',
            'page_size' => 'A4',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
        ]);
        $response->assertJsonStructure([
            'assigned_count',
            'total_amount',
        ]);
    }

    public function test_preview_endpoint_compiles_form_35_and_cashbook_data(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        // 1. Form 35 Preview
        $f35Response = $this->actingAs($divisionUser)->postJson(route('division.final-voucher-cashbook.preview'), [
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
            'report_type' => 'form_35',
        ]);

        $f35Response->assertStatus(200);
        $f35Response->assertJson([
            'success' => true,
            'report_type' => 'form_35',
        ]);
        $f35Response->assertJsonStructure([
            'data' => [
                'division_name',
                'entries',
                'totals',
            ],
        ]);

        // 2. Cash Book Preview
        $cbResponse = $this->actingAs($divisionUser)->postJson(route('division.final-voucher-cashbook.preview'), [
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
            'report_type' => 'cashbook',
        ]);

        $cbResponse->assertStatus(200);
        $cbResponse->assertJson([
            'success' => true,
            'report_type' => 'cashbook',
        ]);
        $cbResponse->assertJsonStructure([
            'data' => [
                'cashbook_summary' => [
                    'opening_balance',
                    'total_receipts',
                    'total_payments',
                    'closing_balance',
                ],
            ],
        ]);
    }

    public function test_print_endpoint_renders_printable_document(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('division.final-voucher-cashbook.print', [
            'month' => 'Jul',
            'payment_mode' => 'IFMS',
            'report_type' => 'cashbook',
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT STATE FOREST DEPARTMENT');
        $response->assertSee('DIVISION CASH BOOK');
        $response->assertSee('તૈયાર કરનાર (Senior Clerk / Accountant)');
        $response->assertSee('નાયબ વન સંરક્ષક (Deputy Conservator of Forests)');
    }

    public function test_merge_schemes_endpoint_saves_group(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->postJson(route('division.final-voucher-cashbook.merge-schemes'), [
            'group_name' => 'Bamboo & Plantation Merged Group',
            'schemes' => ['2406- National Bamboo Mission', 'Agroforestry Under...'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_form_35_lists_approved_vouchers_of_the_month_in_final_number_order(): void
    {
        [$d, $r] = $this->division();
        $this->voucher($r, ['voucher_no_final' => 'SR-2', 'payable_total_amount' => 2000, 'net_amount' => 1960]);
        $this->voucher($r, ['voucher_no_final' => 'SR-1']);
        $this->voucher($r, ['voucher_no_final' => 'CONT-1', 'ifms_bill_type' => 'Contingency']);
        $this->voucher($r, ['voucher_no_final' => 'SR-1', 'treasury_approval_date' => '2026-07-02']);           // other month
        $this->voucher($r, ['treasury_status' => 'Pending', 'treasury_approval_date' => null, 'voucher_no_final' => null]); // not approved
        $this->voucher($r, ['payment_mode' => 'SNA', 'ifms_bill_type' => 'SNA', 'voucher_no_final' => 'SNA-1']);  // other mode

        $json = $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'payment_mode' => 'IFMS', 'report_type' => 'form_35'])->assertOk()->json('data');

        $this->assertSame(['SR-1', 'SR-2', 'CONT-1'], array_column($json['entries'], 'voucher_no'));
        $this->assertSame(3, $json['totals']['count']);
        $this->assertEquals(4000, $json['totals']['gross_amount']);
        $this->assertEquals(3920, $json['totals']['net_amount']);
        $this->assertEquals(60, $json['totals']['total_deductions']);
    }

    public function test_assign_numbers_continue_the_months_series_and_restart_each_month(): void
    {
        [$d, $r] = $this->division();
        $this->voucher($r, ['voucher_no_final' => 'SR-5']);                                                // June already at 5
        $this->voucher($r, ['voucher_no_final' => 'SR-9', 'treasury_approval_date' => '2026-07-03']);     // July at 9
        $a = $this->voucher($r);                                                                          // June, unnumbered
        $b = $this->voucher($r);
        $j = $this->voucher($r, ['treasury_approval_date' => '2026-07-20']);                              // July, unnumbered

        $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.assign'), ['month' => 'Jun', 'payment_mode' => 'IFMS'])->assertOk()->assertJsonPath('assigned_count', 2);
        $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.assign'), ['month' => 'Jul', 'payment_mode' => 'IFMS'])->assertOk()->assertJsonPath('assigned_count', 1);

        $this->assertSame(['SR-6', 'SR-7'], [$a->fresh()->voucher_no_final, $b->fresh()->voucher_no_final]);
        $this->assertSame('SR-10', $j->fresh()->voucher_no_final);
        $this->assertSame(1, DB::table('voucher_versions')->where('voucher_id', $j->id)->where('version_kind', 'advice:final_number')->count(), 'every number leaves an audit version');

        // nothing left to number: says so, changes nothing
        $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.assign'), ['month' => 'Jun', 'payment_mode' => 'IFMS'])->assertOk()->assertJsonPath('assigned_count', 0);
    }

    public function test_cash_book_rolls_receipts_and_payments_forward_without_invented_balances(): void
    {
        [$d, $r] = $this->division();
        LcEntry::create(['division_id' => $d->id, 'scheme' => 'Divisional', 'class' => 'Class-2', 'entry_date' => '2026-05-15', 'amount' => 10000]);
        LcEntry::create(['division_id' => $d->id, 'scheme' => 'Divisional', 'class' => 'Class-2', 'entry_date' => '2026-06-05', 'amount' => 5000]);
        $this->voucher($r, ['treasury_approval_date' => '2026-05-20', 'payable_total_amount' => 3000, 'voucher_no_final' => 'SR-1']);
        $this->voucher($r, ['voucher_no_final' => 'SR-1', 'payable_total_amount' => 2000]);

        $json = $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'payment_mode' => 'SNA', 'report_type' => 'cashbook'])->assertOk()->json();

        $this->assertSame('All modes', $json['payment_mode']);     // L.C. is not split by mode
        $s = $json['data']['cashbook_summary'];
        $this->assertEquals(7000, $s['opening_balance']);          // 10,000 L.C. - 3,000 paid before June
        $this->assertEquals(5000, $s['total_receipts']);
        $this->assertEquals(2000, $s['total_payments']);
        $this->assertEquals(10000, $s['closing_balance']);
        $this->assertStringContainsString('not recorded', $json['data']['cashbook_note']);
    }

    public function test_merged_scheme_groups_are_saved_and_used_in_form_35(): void
    {
        [$d, $r] = $this->division();
        $this->voucher($r, ['voucher_no_final' => 'SR-1', 'scheme' => 'Bamboo']);
        $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.merge-schemes'), ['group_name' => 'Plantation group', 'schemes' => ['Bamboo', 'RDFL']])->assertOk();

        $this->assertSame(1, DB::table('division_scheme_groups')->count());
        $this->actingAs($d)->get(route('division.final-voucher-cashbook.index'))->assertSee('Plantation group');
        $json = $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'payment_mode' => 'IFMS', 'report_type' => 'form_35'])->json('data');
        $this->assertSame('Plantation group', $json['entries'][0]['scheme_name']);
    }

    public function test_nothing_is_invented_when_there_are_no_vouchers(): void
    {
        [$d] = $this->division();

        $page = $this->actingAs($d)->get(route('division.final-voucher-cashbook.print', ['month' => 'Jun', 'payment_mode' => 'IFMS', 'report_type' => 'form_35']));
        $page->assertOk();
        foreach (['Shree Ram Construction', 'Gir Forest Labour', 'FV-001', 'Gujarat Agro Forestry'] as $demo) {
            $page->assertDontSee($demo);
        }
        $cb = $this->actingAs($d)->postJson(route('division.final-voucher-cashbook.preview'), ['month' => 'Jun', 'report_type' => 'cashbook'])->assertOk()->json('data.cashbook_summary');
        $this->assertEquals(0, $cb['opening_balance']);
        $this->assertEquals(0, $cb['closing_balance']);
        $this->actingAs($d)->get(route('division.final-voucher-cashbook.index'))->assertOk()->assertDontSee('General Forest Scheme Group');
    }
}
