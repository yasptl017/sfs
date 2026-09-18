<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_division_user_sees_omni_search_and_division_catalog(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DIVISION]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Quick Search');
        $response->assertSee('omniSearchInput');
        $response->assertSee('Monthly Reports');
        $response->assertSee('Final Voucher Nos');
        $response->assertSee('Office Profile');
    }

    public function test_range_user_sees_omni_search_and_range_catalog(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RANGE]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Quick Search');
        $response->assertSee('omniSearchInput');
        $response->assertSee('Voucher Print');
        $response->assertSee('Abstract Print');
        $response->assertSee('Work Order Print');
        $response->assertSee('Vavetar Plantation Register');
    }

    public function test_admin_user_sees_administrative_catalog_in_search(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Quick Search');
        $response->assertSee('omniSearchInput');
        $response->assertSee('Division Accounts Administration');
        $response->assertSee('Budget Codes Master');
    }
}
