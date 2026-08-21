<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Finance\PartyRegistrationController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\RangeController;
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
    Route::get('ranges', [RangeController::class, 'index'])->name('ranges.index');
    Route::post('ranges', [RangeController::class, 'store'])->name('ranges.store');
    Route::get('finance/registration/party', [PartyRegistrationController::class, 'create'])->name('finance.registration.party');
    Route::post('finance/registration/party', [PartyRegistrationController::class, 'store'])->name('finance.registration.party.store');
    Route::get('finance/registration/party/copy/{serialNumber}', [PartyRegistrationController::class, 'copy'])->name('finance.registration.party.copy');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
