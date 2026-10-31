<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GateScanController;
use App\Http\Controllers\GuardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

/*
|--------------------------------------------------------------------------
| Visitor Portal
|--------------------------------------------------------------------------
*/
Route::prefix('visitor')->name('visitor.')->group(function () {
    Route::get('register', [VisitorController::class, 'showRegister'])->name('register');
    Route::post('register', [VisitorController::class, 'register']);

    Route::get('login', [VisitorController::class, 'showLogin'])->name('login');
    Route::post('login', [VisitorController::class, 'login']);
    Route::get('logout', [VisitorController::class, 'logout'])->name('logout');

    Route::get('status', [VisitorController::class, 'status'])
        ->middleware('visitor.auth')
        ->name('status');
});

/*
|--------------------------------------------------------------------------
| Shared Admin/Guard login
|--------------------------------------------------------------------------
*/
Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('login', [StaffAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [StaffAuthController::class, 'login']);
    Route::get('logout', [StaffAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Guard Terminal (admin can also access, matching the Node app)
|--------------------------------------------------------------------------
*/
Route::prefix('guard')->name('guard.')->middleware('staff:guard,admin')->group(function () {
    Route::get('/', [GuardController::class, 'index'])->name('index');
    Route::post('select-gate', [GuardController::class, 'selectGate'])->name('selectGate');
    Route::get('dashboard', [GuardController::class, 'dashboard'])->name('dashboard');

    Route::get('walkin', [GuardController::class, 'showWalkin'])->name('walkin');
    Route::post('walkin', [GuardController::class, 'storeWalkin'])->name('walkin.store');

    // Guard Terminal: gate selector, QR scan, daily gate log, edit info
    Route::get('scan', [GateScanController::class, 'terminal'])->name('scan');
    Route::post('terminal/gate', [GateScanController::class, 'selectGate'])->name('terminal.selectGate');
    Route::post('scan/verify', [GateScanController::class, 'verify'])->name('scan.verify');
    Route::get('terminal/log', [GateScanController::class, 'dailyLog'])->name('terminal.log');
    Route::post('terminal/records/{type}/{id}', [GateScanController::class, 'updateRecord'])
        ->whereIn('type', ['visitor', 'walk_in'])
        ->whereNumber('id')
        ->name('terminal.records.update');

    // Gate-restricted PDF report
    Route::get('reports', [ReportController::class, 'guardIndex'])->name('reports');
    Route::get('reports/pdf', [ReportController::class, 'guardPdf'])->name('reports.pdf');

    Route::get('walkins-log', [GuardController::class, 'walkinsLog'])->name('walkinsLog');
    Route::post('walkins-log/{id}/{mode}', [GuardController::class, 'walkinsLogAction'])
        ->whereNumber('id')
        ->whereIn('mode', ['in', 'out'])
        ->name('walkinsLog.action');
});

/*
|--------------------------------------------------------------------------
| Admin Console
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('staff:admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::post('registrations/{id}/approve', [AdminController::class, 'approveRegistration'])
        ->whereNumber('id')->name('registrations.approve');
    Route::post('registrations/{id}/deny', [AdminController::class, 'denyRegistration'])
        ->whereNumber('id')->name('registrations.deny');
    Route::post('registrations/{id}/blacklist', [AdminController::class, 'blacklistRegistration'])
        ->whereNumber('id')->name('registrations.blacklist');

    Route::get('walkins', [AdminController::class, 'walkins'])->name('walkins');
    Route::post('walkins/{id}/approve', [AdminController::class, 'approveWalkin'])
        ->whereNumber('id')->name('walkins.approve');
    Route::post('walkins/{id}/deny', [AdminController::class, 'denyWalkin'])
        ->whereNumber('id')->name('walkins.deny');

    Route::get('blacklist', [AdminController::class, 'blacklist'])->name('blacklist');
    Route::post('blacklist/add', [AdminController::class, 'blacklistAdd'])->name('blacklist.add');
    Route::post('blacklist/{id}/reinstate', [AdminController::class, 'blacklistReinstate'])
        ->whereNumber('id')->name('blacklist.reinstate');
    Route::post('blacklist/{id}/remove', [AdminController::class, 'blacklistRemove'])
        ->whereNumber('id')->name('blacklist.remove');

    Route::get('visitors', [AdminController::class, 'visitors'])->name('visitors');
    Route::post('visitors/{id}/update', [AdminController::class, 'updateVisitor'])
        ->whereNumber('id')->name('visitors.update');
    Route::post('visitors/{id}/delete', [AdminController::class, 'deleteVisitor'])
        ->whereNumber('id')->name('visitors.delete');

    Route::get('live-traffic', [AdminController::class, 'liveTraffic'])->name('liveTraffic');
    Route::get('history', [AdminController::class, 'history'])->name('history');

    Route::get('reports', [AdminController::class, 'showReports'])->name('reports');
    Route::get('reports/pdf', [AdminController::class, 'reportsPdf'])->name('reports.pdf');
});
