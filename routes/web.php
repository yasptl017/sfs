<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\Finance\PartyRegistrationController;
use App\Http\Controllers\Admin\Finance\WlBeneficiaryController;
use App\Http\Controllers\Admin\Finance\SfBeneficiaryController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\RangeController;
use App\Http\Controllers\Admin\RangeLocationController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('admin/divisions', [DivisionController::class, 'index'])->name('admin.divisions.index');
    Route::post('admin/divisions', [DivisionController::class, 'store'])->name('admin.divisions.store');
    Route::put('admin/divisions/{division}', [DivisionController::class, 'update'])->name('admin.divisions.update');
    Route::delete('admin/divisions/{division}', [DivisionController::class, 'destroy'])->name('admin.divisions.destroy');
    Route::get('ranges', [RangeController::class, 'index'])->name('ranges.index');
    Route::post('ranges', [RangeController::class, 'store'])->name('ranges.store');
    Route::get('range-locations', [RangeLocationController::class, 'index'])->name('range-locations.index');
    Route::post('range-locations', [RangeLocationController::class, 'store'])->name('range-locations.store');
    Route::put('range-locations/{location}', [RangeLocationController::class, 'update'])->name('range-locations.update');
    Route::delete('range-locations/{location}', [RangeLocationController::class, 'destroy'])->name('range-locations.destroy');
    Route::get('finance/registration/parties', [PartyRegistrationController::class, 'index'])->name('finance.registration.parties.index');
    Route::get('finance/registration/party', [PartyRegistrationController::class, 'create'])->name('finance.registration.party');
    Route::post('finance/registration/party', [PartyRegistrationController::class, 'store'])->name('finance.registration.party.store');
    Route::get('finance/registration/party/copy/{serialNumber}', [PartyRegistrationController::class, 'copy'])->name('finance.registration.party.copy');
    Route::get('finance/registration/party/{party}/edit', [PartyRegistrationController::class, 'edit'])->name('finance.registration.party.edit');
    Route::put('finance/registration/party/{party}', [PartyRegistrationController::class, 'update'])->name('finance.registration.party.update');
    Route::patch('finance/registration/party/{party}/status', [PartyRegistrationController::class, 'toggleStatus'])->name('finance.registration.party.status');
    Route::delete('finance/registration/party/{party}', [PartyRegistrationController::class, 'destroy'])->name('finance.registration.party.destroy');
    Route::get('finance/registration/wl-beneficiaries', [WlBeneficiaryController::class, 'index'])->name('finance.registration.wl-beneficiaries.index');
    Route::get('finance/registration/wl-beneficiary', [WlBeneficiaryController::class, 'create'])->name('finance.registration.wl-beneficiaries.create');
    Route::post('finance/registration/wl-beneficiary', [WlBeneficiaryController::class, 'store'])->name('finance.registration.wl-beneficiaries.store');
    Route::get('finance/registration/wl-beneficiary/{beneficiary}/edit', [WlBeneficiaryController::class, 'edit'])->name('finance.registration.wl-beneficiaries.edit');
    Route::put('finance/registration/wl-beneficiary/{beneficiary}', [WlBeneficiaryController::class, 'update'])->name('finance.registration.wl-beneficiaries.update');
    Route::patch('finance/registration/wl-beneficiary/{beneficiary}/status', [WlBeneficiaryController::class, 'toggleStatus'])->name('finance.registration.wl-beneficiaries.status');
    Route::delete('finance/registration/wl-beneficiary/{beneficiary}', [WlBeneficiaryController::class, 'destroy'])->name('finance.registration.wl-beneficiaries.destroy');
    Route::get('finance/registration/sf-beneficiaries', [SfBeneficiaryController::class, 'index'])->name('finance.registration.sf-beneficiaries.index');
    Route::get('finance/registration/sf-beneficiary', [SfBeneficiaryController::class, 'create'])->name('finance.registration.sf-beneficiaries.create');
    Route::post('finance/registration/sf-beneficiary', [SfBeneficiaryController::class, 'store'])->name('finance.registration.sf-beneficiaries.store');
    Route::get('finance/registration/sf-beneficiary/{beneficiary}/edit', [SfBeneficiaryController::class, 'edit'])->name('finance.registration.sf-beneficiaries.edit');
    Route::put('finance/registration/sf-beneficiary/{beneficiary}', [SfBeneficiaryController::class, 'update'])->name('finance.registration.sf-beneficiaries.update');
    Route::patch('finance/registration/sf-beneficiary/{beneficiary}/status', [SfBeneficiaryController::class, 'toggleStatus'])->name('finance.registration.sf-beneficiaries.status');
    Route::delete('finance/registration/sf-beneficiary/{beneficiary}', [SfBeneficiaryController::class, 'destroy'])->name('finance.registration.sf-beneficiaries.destroy');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
