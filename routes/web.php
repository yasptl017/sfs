<?php

use App\Http\Controllers\Admin\BudgetCodeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Finance\AdviceController;
use App\Http\Controllers\Admin\FinancialYearController;
use App\Http\Controllers\Admin\PositionReportController;
use App\Http\Controllers\Admin\RegisterBrowseController;
use App\Http\Controllers\Admin\VoucherEntryController;
use App\Http\Controllers\Admin\VoucherBrowseController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\Finance\FinalVoucherCashbookController;
use App\Http\Controllers\Admin\Finance\MonthlyReportController;
use App\Http\Controllers\Admin\Finance\SummaryReportController;
use App\Http\Controllers\Admin\Finance\PartyRegistrationController;
use App\Http\Controllers\Admin\Finance\WlBeneficiaryController;
use App\Http\Controllers\Admin\Finance\SfBeneficiaryController;
use App\Http\Controllers\Admin\Finance\DivisionTenderPartyController;
use App\Http\Controllers\Admin\Finance\CashAccountController;
use App\Http\Controllers\Admin\Finance\AllotmentFromCircleController;
use App\Http\Controllers\Admin\Finance\AllotmentToRangeController;
use App\Http\Controllers\Admin\Finance\AllotmentAdjustmentController;
use App\Http\Controllers\Admin\Finance\LcEntryController;
use App\Http\Controllers\Admin\Finance\AbstractPrintController;
use App\Http\Controllers\Admin\Finance\WorkOrderPrintController;
use App\Http\Controllers\Admin\Finance\VavetarRegisterController;
use App\Http\Controllers\Admin\Finance\VoucherPrintController;
use App\Http\Controllers\Admin\Finance\SorLimitReportController;
use App\Http\Controllers\Admin\OfficeProfileController;
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
    Route::post('financial-year', [FinancialYearController::class, 'switch'])->name('financial-year.switch');
    Route::get('vouchers', [VoucherBrowseController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/{voucher}', [VoucherBrowseController::class, 'show'])->whereNumber('voucher')->name('vouchers.show');
    Route::get('vouchers/{voucher}/print', [VoucherBrowseController::class, 'print'])->whereNumber('voucher')->name('vouchers.print');
    Route::get('reports/position', [PositionReportController::class, 'index'])->name('reports.position');
    Route::get('registers/{key}', [RegisterBrowseController::class, 'index'])->name('registers.index');
    Route::get('registers/{key}/{id}', [RegisterBrowseController::class, 'show'])->whereNumber('id')->name('registers.show');

    // Voucher entry (all six types, typed tables). Legacy URLs and route names are kept so menus and links keep working.
    foreach ([
        'tender' => ['tender-entries', 'tender-entry', 'Tender Entry'], 'free' => ['free-entries', 'free-entry', 'Free Entry'],
        'dw-salary' => ['d-wager-salary-entries', 'd-wager-salary-entry', 'D.Wagers Salary'], 'dw-arrears' => ['d-wager-arrears-entries', 'd-wager-arrears-entry', 'D.Wagers Arrears'],
        'sf-bene' => ['sf-beneficiary-entries', 'sf-beneficiary-entry', 'Bene. Entry'], 'wl-bene' => ['wl-beneficiary-entries', 'wl-beneficiary-entry', 'WL Bene. Entry'],
    ] as $slug => [$plural, $single, $label]) {
        Route::get("finance/$plural", fn () => redirect()->route('vouchers.index', ['entry_type' => $label]))->name("finance.$plural.index");
        Route::get("finance/$single", fn (\Illuminate\Http\Request $r) => app(VoucherEntryController::class)->create($r, $slug))->name("finance.$plural.create");
        Route::post("finance/$single", fn (\Illuminate\Http\Request $r) => app(VoucherEntryController::class)->store($r, $slug))->name("finance.$plural.store");
        Route::get("finance/$single/{voucher}/edit", [VoucherEntryController::class, 'edit'])->whereNumber('voucher')->name("finance.$plural.edit");
        Route::put("finance/$single/{voucher}", [VoucherEntryController::class, 'update'])->whereNumber('voucher')->name("finance.$plural.update");
        Route::delete("finance/$single/{voucher}", [VoucherEntryController::class, 'destroy'])->whereNumber('voucher')->name("finance.$plural.destroy");
    }
    Route::get('division/parties', [DivisionTenderPartyController::class, 'index'])->name('division.parties.index');
    Route::get('division/parties/create', [DivisionTenderPartyController::class, 'create'])->name('division.parties.create');
    Route::post('division/parties', [DivisionTenderPartyController::class, 'store'])->name('division.parties.store');
    Route::get('division/parties/copy/{serialNumber}', [DivisionTenderPartyController::class, 'copy'])->name('division.parties.copy');
    Route::get('division/parties/{party}/edit', [DivisionTenderPartyController::class, 'edit'])->name('division.parties.edit');
    Route::put('division/parties/{party}', [DivisionTenderPartyController::class, 'update'])->name('division.parties.update');
    Route::patch('division/parties/{party}/status', [DivisionTenderPartyController::class, 'toggleStatus'])->name('division.parties.status');
    Route::delete('division/parties/{party}', [DivisionTenderPartyController::class, 'destroy'])->name('division.parties.destroy');
    Route::get('division/cash-accounts', [CashAccountController::class, 'index'])->name('division.cash-accounts.index');
    Route::get('division/cash-account', [CashAccountController::class, 'create'])->name('division.cash-accounts.create');
    Route::post('division/cash-account', [CashAccountController::class, 'store'])->name('division.cash-accounts.store');
    Route::get('division/allotments-from-circle', [AllotmentFromCircleController::class, 'index'])->name('division.allotments-from-circle.index');
    Route::get('division/allotment-from-circle', [AllotmentFromCircleController::class, 'create'])->name('division.allotments-from-circle.create');
    Route::post('division/allotment-from-circle', [AllotmentFromCircleController::class, 'store'])->name('division.allotments-from-circle.store');
    Route::get('division/allotment-from-circle/{allotmentFromCircle}/edit', [AllotmentFromCircleController::class, 'edit'])->name('division.allotments-from-circle.edit');
    Route::put('division/allotment-from-circle/{allotmentFromCircle}', [AllotmentFromCircleController::class, 'update'])->name('division.allotments-from-circle.update');
    Route::delete('division/allotment-from-circle/{allotmentFromCircle}', [AllotmentFromCircleController::class, 'destroy'])->name('division.allotments-from-circle.destroy');
    Route::get('division/allotment-to-range', [AllotmentToRangeController::class, 'create'])->name('division.allotments-to-range.create');
    Route::post('division/allotment-to-range', [AllotmentToRangeController::class, 'store'])->name('division.allotments-to-range.store');
    Route::get('division/allotment-to-range/budget-details/{budgetCode}', [AllotmentToRangeController::class, 'budgetDetails'])->name('division.allotments-to-range.budget-details');
    Route::get('division/allotment-adjustments', [AllotmentAdjustmentController::class, 'index'])->name('division.allotment-adjustments.index');
    Route::get('division/allotment-adjustment', [AllotmentAdjustmentController::class, 'create'])->name('division.allotment-adjustments.create');
    Route::post('division/allotment-adjustment', [AllotmentAdjustmentController::class, 'store'])->name('division.allotment-adjustments.store');
    Route::get('division/allotment-adjustment/budget-details/{budgetCode}', [AllotmentAdjustmentController::class, 'details'])->name('division.allotment-adjustments.details');
    Route::get('division/allotment-adjustment/{allotmentAdjustment}/edit', [AllotmentAdjustmentController::class, 'edit'])->name('division.allotment-adjustments.edit');
    Route::put('division/allotment-adjustment/{allotmentAdjustment}', [AllotmentAdjustmentController::class, 'update'])->name('division.allotment-adjustments.update');
    Route::delete('division/allotment-adjustment/{allotmentAdjustment}', [AllotmentAdjustmentController::class, 'destroy'])->name('division.allotment-adjustments.destroy');
    Route::get('division/lc-entries', [LcEntryController::class, 'index'])->name('division.lc-entries.index');
    Route::get('division/lc-entry', [LcEntryController::class, 'create'])->name('division.lc-entries.create');
    Route::post('division/lc-entry', [LcEntryController::class, 'store'])->name('division.lc-entries.store');
    Route::get('division/lc-entry/{lcEntry}/edit', [LcEntryController::class, 'edit'])->name('division.lc-entries.edit');
    Route::put('division/lc-entry/{lcEntry}', [LcEntryController::class, 'update'])->name('division.lc-entries.update');
    Route::delete('division/lc-entry/{lcEntry}', [LcEntryController::class, 'destroy'])->name('division.lc-entries.destroy');

    // Division bill processing on the voucher columns. Legacy route names keep the menu working.
    Route::get('division/advices', [AdviceController::class, 'index'])->name('division.advices.index');
    Route::get('division/advices/pending', [AdviceController::class, 'pending'])->name('division.advices.pending');
    Route::post('division/advices', [AdviceController::class, 'store'])->name('division.advices.store');
    Route::post('division/advices/receive', [AdviceController::class, 'receive'])->name('division.advices.receive');
    Route::get('division/advices/{adviceNo}', [AdviceController::class, 'show'])->whereNumber('adviceNo')->name('division.advices.show');
    Route::put('division/advices/{adviceNo}/treasury', [AdviceController::class, 'treasury'])->whereNumber('adviceNo')->name('division.advices.treasury');
    Route::put('division/advices/{adviceNo}/gst', [AdviceController::class, 'gst'])->whereNumber('adviceNo')->name('division.advices.gst');
    Route::put('division/advices/{adviceNo}/numbers', [AdviceController::class, 'numbers'])->whereNumber('adviceNo')->name('division.advices.numbers');
    Route::post('division/advices/{adviceNo}/final-numbers', [AdviceController::class, 'finalNumbers'])->whereNumber('adviceNo')->name('division.advices.final');
    Route::delete('division/advices/{adviceNo}', [AdviceController::class, 'destroy'])->whereNumber('adviceNo')->name('division.advices.destroy');
    Route::get('division/process-bill', fn () => redirect()->route('division.advices.pending'))->name('division.process-bill.index');
    foreach (['gst-challans' => 'division.gst-challan.index', 'bill-advice-reports' => 'division.bill-advice-reports.index', 'treasury-details' => 'division.treasury-details.index',
        'change-bill-order-no' => 'division.change-bill-order-no.index', 'delete-advice' => 'division.delete-advice.index'] as $uri => $name) {
        Route::get("division/$uri", fn () => redirect()->route('division.advices.index'))->name($name);
    }

    Route::get('division/monthly-reports', [MonthlyReportController::class, 'index'])->name('division.monthly-reports.index');
    Route::get('division/monthly-reports/schemes', [MonthlyReportController::class, 'schemes'])->name('division.monthly-reports.schemes');
    Route::post('division/monthly-reports/preview', [MonthlyReportController::class, 'preview'])->name('division.monthly-reports.preview');
    Route::get('division/monthly-reports/print', [MonthlyReportController::class, 'print'])->name('division.monthly-reports.print');

    Route::get('division/summary-reports', [SummaryReportController::class, 'index'])->name('division.summary-reports.index');
    Route::post('division/summary-reports/preview', [SummaryReportController::class, 'preview'])->name('division.summary-reports.preview');
    Route::get('division/summary-reports/print', [SummaryReportController::class, 'print'])->name('division.summary-reports.print');

    Route::get('division/final-voucher-cashbook', [FinalVoucherCashbookController::class, 'index'])->name('division.final-voucher-cashbook.index');
    Route::post('division/final-voucher-cashbook/assign', [FinalVoucherCashbookController::class, 'assignVouchers'])->name('division.final-voucher-cashbook.assign');
    Route::post('division/final-voucher-cashbook/preview', [FinalVoucherCashbookController::class, 'preview'])->name('division.final-voucher-cashbook.preview');
    Route::get('division/final-voucher-cashbook/print', [FinalVoucherCashbookController::class, 'print'])->name('division.final-voucher-cashbook.print');
    Route::post('division/final-voucher-cashbook/merge-schemes', [FinalVoucherCashbookController::class, 'mergeSchemes'])->name('division.final-voucher-cashbook.merge-schemes');
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
    Route::put('ranges/{range}', [RangeController::class, 'update'])->name('ranges.update');
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







    Route::get('finance/voucher-print', [VoucherPrintController::class, 'index'])->name('finance.voucher-print.index');
    Route::get('finance/voucher-print/entries', [VoucherPrintController::class, 'entries'])->name('finance.voucher-print.entries');
    Route::post('finance/voucher-print/preview', [VoucherPrintController::class, 'preview'])->name('finance.voucher-print.preview');
    Route::get('finance/voucher-print/print', [VoucherPrintController::class, 'print'])->name('finance.voucher-print.print');

    Route::get('finance/abstract-print', [AbstractPrintController::class, 'index'])->name('finance.abstract-print.index');
    Route::get('finance/abstract-print/dockets', [AbstractPrintController::class, 'dockets'])->name('finance.abstract-print.dockets');
    Route::post('finance/abstract-print/preview', [AbstractPrintController::class, 'preview'])->name('finance.abstract-print.preview');
    Route::get('finance/abstract-print/print', [AbstractPrintController::class, 'print'])->name('finance.abstract-print.print');

    Route::get('finance/work-order-print', [WorkOrderPrintController::class, 'index'])->name('finance.work-order-print.index');
    Route::get('finance/work-order-print/dockets', [WorkOrderPrintController::class, 'dockets'])->name('finance.work-order-print.dockets');
    Route::post('finance/work-order-print/preview', [WorkOrderPrintController::class, 'preview'])->name('finance.work-order-print.preview');
    Route::get('finance/work-order-print/print', [WorkOrderPrintController::class, 'print'])->name('finance.work-order-print.print');

    Route::get('finance/vavetar-register', [VavetarRegisterController::class, 'index'])->name('finance.vavetar-register.index');
    Route::get('finance/vavetar-register/locations', [VavetarRegisterController::class, 'locations'])->name('finance.vavetar-register.locations');
    Route::post('finance/vavetar-register/preview', [VavetarRegisterController::class, 'preview'])->name('finance.vavetar-register.preview');
    Route::get('finance/vavetar-register/print', [VavetarRegisterController::class, 'print'])->name('finance.vavetar-register.print');

    Route::get('finance/sor-limit-report', [SorLimitReportController::class, 'index'])->name('finance.sor-limit-report.index');
    Route::get('finance/sor-limit-report/filters', [SorLimitReportController::class, 'filters'])->name('finance.sor-limit-report.filters');
    Route::post('finance/sor-limit-report/preview', [SorLimitReportController::class, 'preview'])->name('finance.sor-limit-report.preview');
    Route::get('finance/sor-limit-report/print', [SorLimitReportController::class, 'print'])->name('finance.sor-limit-report.print');
    Route::get('finance/sor-limit-report/export', [SorLimitReportController::class, 'export'])->name('finance.sor-limit-report.export');

    Route::get('office-profile', [OfficeProfileController::class, 'edit'])->name('office-profile.edit');
    Route::put('office-profile', [OfficeProfileController::class, 'update'])->name('office-profile.update');
    Route::delete('office-profile/logo', [OfficeProfileController::class, 'removeLogo'])->name('office-profile.logo.destroy');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
