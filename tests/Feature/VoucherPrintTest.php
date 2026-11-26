<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherPrintTest extends TestCase
{
    use RefreshDatabase;

    /** A real voucher of the range (the print reads the typed voucher tables). */
    private function voucher(User $range, int $serial, array $over = []): Voucher
    {
        return Voucher::create(array_merge([
            'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $range->id, 'entry_type' => 'Tender Entry', 'serial_number' => $serial,
            'party_name' => 'Party '.$serial, 'budget_code' => 'A3', 'scheme' => '02- Divisional', 'entry_month' => 'Jun', 'payable_total_amount' => 10000, 'total_deduction' => 200, 'net_amount' => 9800,
        ], $over));
    }

    public function test_range_user_can_access_voucher_print_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $response = $this->actingAs($rangeUser)->get(route('finance.voucher-print.index'));

        $response->assertStatus(200);
        $response->assertSee('Voucher Print:');
        $response->assertSee('Which entry to print ?');
        $response->assertSee('Serial numbers');
        $response->assertSee('seperated by comma / dash');
        $response->assertSee('With Work Order Numbers');
        $response->assertSee('Fit to page');
        $response->assertSee('Print');
        $response->assertSee('Tender Entry');
        $response->assertSee('Free Entry');
        $response->assertSee('D.Wagers Salary');
        $response->assertSee('D.Wagers Arrears');
        $response->assertSee('WL Bene. Entry');
    }

    public function test_division_user_cannot_access_voucher_print_page(): void
    {
        $divisionUser = User::factory()->create(['role' => 'division']);

        $response = $this->actingAs($divisionUser)->get(route('finance.voucher-print.index'));

        $response->assertStatus(403);
    }

    public function test_entries_endpoint_returns_json_for_selected_type(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $this->voucher($rangeUser, 1, ['party_name' => 'Shree Ram Construction', 'budget_code' => '2406-01-101-01', 'docket_no' => 1, 'payable_total_amount' => 45000]);

        $response = $this->actingAs($rangeUser)->getJson(route('finance.voucher-print.entries', [
            'entry_type' => 'Tender Entry',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'entry_type' => 'Tender Entry',
        ]);
        $response->assertJsonFragment([
            'serial_number' => 1,
            'party_name' => 'Shree Ram Construction',
        ]);
    }

    public function test_preview_endpoint_returns_compiled_vouchers(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);

        $v = $this->voucher($rangeUser, 5, ['party_name' => 'Gujarat Agro Forestry', 'budget_code' => '2406-01-101-02', 'payable_total_amount' => 30000, 'net_amount' => 30000, 'total_deduction' => 0]);
        $v->items()->create(['line_no' => 1, 'sor_code' => '1', 'description' => 'Plantation digging', 'quantity' => 100, 'unit' => 'No.', 'rate' => 300, 'amount' => 30000, 'raw_line' => '[]']);

        $response = $this->actingAs($rangeUser)->postJson(route('finance.voucher-print.preview'), [
            'entry_type' => 'Tender Entry',
            'serial_numbers' => '5',
            'with_work_order' => 1,
            'fit_to_page' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment([
            'serial_number' => 5,
            'party_name' => 'Gujarat Agro Forestry',
        ]);
    }

    public function test_print_endpoint_renders_printable_page(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        foreach ([1, 2, 3] as $sr) {
            $this->voucher($rangeUser, $sr);
        }

        $response = $this->actingAs($rangeUser)->get(route('finance.voucher-print.print', [
            'entry_type' => 'Tender Entry',
            'serial_numbers' => '1-3',
            'with_work_order' => 1,
            'fit_to_page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('GUJARAT FOREST DEPARTMENT');
        $response->assertSee('Tender Entry VOUCHER (FORM NO. 35)');
        $response->assertSee('Deductions');
        $response->assertSee('Net Payable Amount');
    }

    public function test_serial_numbers_that_do_not_exist_are_reported_never_invented(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $this->voucher($rangeUser, 2, ['party_name' => 'Real Party']);

        $json = $this->actingAs($rangeUser)->postJson(route('finance.voucher-print.preview'), ['entry_type' => 'Tender Entry', 'serial_numbers' => '1-3'])->assertOk()->json();

        $this->assertSame(1, $json['count']);
        $this->assertSame([1, 3], $json['missing']);
        $this->assertSame('Real Party', $json['vouchers'][0]['party_name']);

        $page = $this->actingAs($rangeUser)->get(route('finance.voucher-print.print', ['entry_type' => 'Tender Entry', 'serial_numbers' => '1-3']));
        $page->assertSee('Not found - nothing was printed for');
        $page->assertDontSee('ABCDE');
        $page->assertDontSee('Forest Compartment No. 42');
        $this->actingAs($rangeUser)->get(route('finance.voucher-print.print', ['entry_type' => 'Tender Entry', 'serial_numbers' => '9']))->assertSee('No vouchers found to print');
    }

    public function test_only_the_ranges_own_vouchers_of_the_selected_year_are_printable(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $other = User::factory()->create(['role' => 'range']);
        $this->voucher($other, 1, ['party_name' => 'Somebody Else']);

        $json = $this->actingAs($rangeUser)->postJson(route('finance.voucher-print.preview'), ['entry_type' => 'Tender Entry', 'serial_numbers' => '1'])->json();

        $this->assertSame(0, $json['count']);
        $this->assertSame([1], $json['missing']);
    }

    public function test_the_printed_voucher_carries_the_stored_amounts_and_deductions(): void
    {
        $rangeUser = User::factory()->create(['role' => 'range']);
        $v = $this->voucher($rangeUser, 7, ['voucher_no_final' => 'SR-84', 'deduction_sgst' => 100, 'deduction_cgst' => 100, 'tds' => 200, 'gpf' => 50, 'total_deduction' => 450,
            'payable_total_amount' => 10000, 'net_amount' => 9550, 'bill_date' => '2026-06-12', 'pan_card_no' => 'AAAPB1234C']);
        $v->items()->create(['line_no' => 1, 'description' => 'Work', 'quantity' => 10, 'unit' => 'Nos', 'rate' => 1000, 'amount' => 10000, 'work_order_no' => 'WO/9', 'work_order_date' => '2026-05-01', 'raw_line' => '[]']);

        $d = $this->actingAs($rangeUser)->postJson(route('finance.voucher-print.preview'), ['entry_type' => 'Tender Entry', 'serial_numbers' => '7', 'with_work_order' => 1])->json('vouchers.0');

        $this->assertSame('SR-84', $d['voucher_no']);
        $this->assertSame('12/06/2026', $d['date']);
        $this->assertEquals(10000.0, $d['gross_amount']);
        $this->assertEquals(100.0, $d['deductions']['sgst']);
        $this->assertEquals(200.0, $d['deductions']['tds']);
        $this->assertEquals(50.0, $d['deductions']['other']);
        $this->assertEquals(450.0, $d['deductions']['total']);
        $this->assertEquals(9550.0, $d['net_amount']);
        $this->assertSame('Nine Thousand Five Hundred Fifty Rupees Only', $d['net_amount_in_words']);
        $this->assertSame('WO/9', $d['work_order_no']);
        $this->assertSame('01/05/2026', $d['work_order_date']);
        $this->assertSame('June 2026', $d['month']);
        $this->assertSame('AAAPB1234C', $d['pan_card_no']);
    }

    public function test_serial_ranges_are_parsed(): void
    {
        $c = new \App\Http\Controllers\Admin\Finance\VoucherPrintController();

        $this->assertSame([1, 2, 3, 4, 19, 23, 24, 25, 47], $c->parseSerialNumbers('1-4, 19, 23-25, 47'));
        $this->assertSame([5, 4, 3], $c->parseSerialNumbers('5-3'));
        $this->assertSame([2], $c->parseSerialNumbers('2, 2, x, 0'));
        $this->assertSame([], $c->parseSerialNumbers(''));
    }
}
