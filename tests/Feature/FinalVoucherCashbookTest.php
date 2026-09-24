<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalVoucherCashbookTest extends TestCase
{
    use RefreshDatabase;

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
}
