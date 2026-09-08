<?php

use App\Http\Controllers\Admin\BudgetCodeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\Finance\PartyRegistrationController;
use App\Http\Controllers\Admin\Finance\WlBeneficiaryController;
use App\Http\Controllers\Admin\Finance\SfBeneficiaryController;
use App\Http\Controllers\Admin\Finance\DivisionTenderPartyController;
use App\Http\Controllers\Admin\Finance\TenderEntryController;
use App\Http\Controllers\Admin\Finance\FreeEntryController;
use App\Http\Controllers\Admin\Finance\DWagerSalaryEntryController;
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
    Route::get('division/parties', [DivisionTenderPartyController::class, 'index'])->name('division.parties.index');
    Route::get('division/parties/create', [DivisionTenderPartyController::class, 'create'])->name('division.parties.create');
    Route::post('division/parties', [DivisionTenderPartyController::class, 'store'])->name('division.parties.store');
    Route::get('division/parties/copy/{serialNumber}', [DivisionTenderPartyController::class, 'copy'])->name('division.parties.copy');
    Route::get('division/parties/{party}/edit', [DivisionTenderPartyController::class, 'edit'])->name('division.parties.edit');
    Route::put('division/parties/{party}', [DivisionTenderPartyController::class, 'update'])->name('division.parties.update');
    Route::patch('division/parties/{party}/status', [DivisionTenderPartyController::class, 'toggleStatus'])->name('division.parties.status');
    Route::delete('division/parties/{party}', [DivisionTenderPartyController::class, 'destroy'])->name('division.parties.destroy');
    Route::get('admin/divisions', [DivisionController::class, 'index'])->name('admin.divisions.index');
    Route::post('admin/divisions', [DivisionController::class, 'store'])->name('admin.divisions.store');
    Route::put('admin/divisions/{division}', [DivisionController::class, 'update'])->name('admin.divisions.update');
    Route::delete('admin/divisions/{division}', [DivisionController::class, 'destroy'])->name('admin.divisions.destroy');
    Route::get('admin/budget-codes', [BudgetCodeController::class, 'index'])->name('admin.budget-codes.index');
    Route::get('admin/budget-codes/create', [BudgetCodeController::class, 'create'])->name('admin.budget-codes.create');
    Route::post('admin/budget-codes', [BudgetCodeController::class, 'store'])->name('admin.budget-codes.store');
    Route::get('admin/budget-codes/{budgetCode}/edit', [BudgetCodeController::class, 'edit'])->name('admin.budget-codes.edit');
    Route::put('admin/budget-codes/{budgetCode}', [BudgetCodeController::class, 'update'])->name('admin.budget-codes.update');
    Route::delete('admin/budget-codes/{budgetCode}', [BudgetCodeController::class, 'destroy'])->name('admin.budget-codes.destroy');
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

    Route::get('finance/tender-entries', [TenderEntryController::class, 'index'])->name('finance.tender-entries.index');
    Route::get('finance/tender-entry', [TenderEntryController::class, 'create'])->name('finance.tender-entries.create');
    Route::post('finance/tender-entry', [TenderEntryController::class, 'store'])->name('finance.tender-entries.store');
    Route::get('finance/tender-entry/copy/{serialNumber}', [TenderEntryController::class, 'copy'])->name('finance.tender-entries.copy');
    Route::get('finance/tender-entry/{tenderEntry}/edit', [TenderEntryController::class, 'edit'])->name('finance.tender-entries.edit');
    Route::put('finance/tender-entry/{tenderEntry}', [TenderEntryController::class, 'update'])->name('finance.tender-entries.update');
    Route::delete('finance/tender-entry/{tenderEntry}', [TenderEntryController::class, 'destroy'])->name('finance.tender-entries.destroy');

    Route::get('finance/free-entries', [FreeEntryController::class, 'index'])->name('finance.free-entries.index');
    Route::get('finance/free-entry', [FreeEntryController::class, 'create'])->name('finance.free-entries.create');
    Route::post('finance/free-entry', [FreeEntryController::class, 'store'])->name('finance.free-entries.store');
    Route::get('finance/free-entry/copy/{serialNumber}', [FreeEntryController::class, 'copy'])->name('finance.free-entries.copy');
    Route::get('finance/free-entry/{freeEntry}/edit', [FreeEntryController::class, 'edit'])->name('finance.free-entries.edit');
    Route::put('finance/free-entry/{freeEntry}', [FreeEntryController::class, 'update'])->name('finance.free-entries.update');
    Route::delete('finance/free-entry/{freeEntry}', [FreeEntryController::class, 'destroy'])->name('finance.free-entries.destroy');

    Route::get('finance/d-wager-salary-entries', [DWagerSalaryEntryController::class, 'index'])->name('finance.d-wager-salary-entries.index');
    Route::get('finance/d-wager-salary-entry', [DWagerSalaryEntryController::class, 'create'])->name('finance.d-wager-salary-entries.create');
    Route::post('finance/d-wager-salary-entry', [DWagerSalaryEntryController::class, 'store'])->name('finance.d-wager-salary-entries.store');
    Route::get('finance/d-wager-salary-entry/copy/{serialNumber}', [DWagerSalaryEntryController::class, 'copy'])->name('finance.d-wager-salary-entries.copy');
    Route::get('finance/d-wager-salary-entry/{dWagerSalaryEntry}/edit', [DWagerSalaryEntryController::class, 'edit'])->name('finance.d-wager-salary-entries.edit');
    Route::put('finance/d-wager-salary-entry/{dWagerSalaryEntry}', [DWagerSalaryEntryController::class, 'update'])->name('finance.d-wager-salary-entries.update');
    Route::delete('finance/d-wager-salary-entry/{dWagerSalaryEntry}', [DWagerSalaryEntryController::class, 'destroy'])->name('finance.d-wager-salary-entries.destroy');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
