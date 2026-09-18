<?php

namespace Tests\Feature;

use App\Models\OfficeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_office_profile(): void
    {
        $response = $this->get(route('office-profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_division_user_can_view_office_profile_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DIVISION]);

        $response = $this->actingAs($user)->get(route('office-profile.edit'));
        $response->assertStatus(200);
        $response->assertSee('Office Profile');
        $response->assertSee('Live Report Letterhead Preview');
    }

    public function test_range_user_can_view_office_profile_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RANGE]);

        $response = $this->actingAs($user)->get(route('office-profile.edit'));
        $response->assertStatus(200);
        $response->assertSee('Office Profile');
        $response->assertSee('Official Logo');
    }

    public function test_user_can_update_office_profile_details(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DIVISION]);

        $response = $this->actingAs($user)->put(route('office-profile.update'), [
            'office_name' => 'Deputy Conservator of Forests, Junagadh Division',
            'office_name_gujarati' => 'નાયબ વન સંરક્ષકશ્રીની કચેરી, જૂનાગઢ વન વિભાગ',
            'officer_name' => 'Dr. R. K. Patel, IFS',
            'officer_designation' => 'Deputy Conservator of Forests',
            'officer_designation_gujarati' => 'નાયબ વન સંરક્ષક',
            'office_address' => 'Sardar Baug, Near Railway Station, Gir Road',
            'city' => 'Junagadh',
            'taluka' => 'Junagadh',
            'district' => 'Junagadh',
            'pincode' => '362001',
            'phone' => '0285-2630001',
            'mobile' => '9876543210',
            'email' => 'dfo-junagadh@gujarat.gov.in',
            'fax' => '0285-2630002',
            'ddo_code' => 'DDO-12345',
            'tan_no' => 'AHMD01234F',
        ]);

        $response->assertRedirect(route('office-profile.edit'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('office_profiles', [
            'user_id' => $user->id,
            'office_name' => 'Deputy Conservator of Forests, Junagadh Division',
            'officer_name' => 'Dr. R. K. Patel, IFS',
            'phone' => '0285-2630001',
            'district' => 'Junagadh',
        ]);
    }

    public function test_user_can_upload_custom_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => User::ROLE_RANGE]);
        $logo = UploadedFile::fake()->create('custom_forest_logo.png', 100, 'image/png');

        $response = $this->actingAs($user)->put(route('office-profile.update'), [
            'office_name' => 'Range Forest Officer, Sasan Gir Range',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('office-profile.edit'));
        $response->assertSessionHas('status');

        $profile = $user->officeProfile()->first();
        $this->assertNotNull($profile);
        $this->assertNotNull($profile->logo_path);

        Storage::disk('public')->assertExists($profile->logo_path);
        $this->assertStringContainsString('storage/' . $profile->logo_path, $profile->logo_url);
    }

    public function test_user_can_remove_custom_logo_and_fallback_to_default(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => User::ROLE_DIVISION]);
        $profile = OfficeProfile::create([
            'user_id' => $user->id,
            'office_name' => 'Division Office',
            'logo_path' => 'logos/test_logo.png',
        ]);

        Storage::disk('public')->put('logos/test_logo.png', 'dummy image content');

        $response = $this->actingAs($user)->delete(route('office-profile.logo.destroy'));
        $response->assertRedirect(route('office-profile.edit'));
        $response->assertSessionHas('status');

        Storage::disk('public')->assertMissing('logos/test_logo.png');

        $profile->refresh();
        $this->assertNull($profile->logo_path);
        $this->assertStringContainsString('gujarat-forest-logo.svg', $profile->logo_url);
    }

    public function test_default_fallback_logo_when_profile_empty(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RANGE]);
        $profile = $user->getOrCreateOfficeProfile();

        $this->assertNull($profile->logo_path);
        $this->assertStringContainsString('images/gujarat-forest-logo.svg', $profile->logo_url);
    }
}
