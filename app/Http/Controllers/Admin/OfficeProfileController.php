<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OfficeProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->getOrCreateOfficeProfile();

        return view('admin.office-profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->getOrCreateOfficeProfile();

        $validated = $request->validate([
            'office_name' => ['nullable', 'string', 'max:255'],
            'office_name_gujarati' => ['nullable', 'string', 'max:255'],
            'officer_name' => ['nullable', 'string', 'max:255'],
            'officer_designation' => ['nullable', 'string', 'max:255'],
            'officer_designation_gujarati' => ['nullable', 'string', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'taluka' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'fax' => ['nullable', 'string', 'max:50'],
            'ddo_code' => ['nullable', 'string', 'max:50'],
            'tan_no' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,svg,webp', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('office-logos', 'public');
        }

        unset($validated['logo']);

        $profile->update($validated);

        return redirect()->route('office-profile.edit')
            ->with('status', 'Office profile, contact details, and logo updated successfully.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->officeProfile;

        if ($profile && $profile->logo_path) {
            if (Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->update(['logo_path' => null]);
        }

        return redirect()->route('office-profile.edit')
            ->with('status', 'Custom logo removed. System reset to official default Gujarat Forest Department emblem.');
    }
}
