<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CopilotController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuickAuditController;
use App\Http\Controllers\Admin\RegulatoryDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Admin Authentication (Public Guest)
Route::prefix('admin')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});
Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');

Route::post('/copilot/ask', CopilotController::class)->name('copilot');

// Protected Administrative Portal
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Administrative Floating Copilot
    Route::post('/copilot/ask', CopilotController::class)->name('copilot');

    // Standalone Quick Drawing Compliance Audit
    Route::get('/quick-audit', [QuickAuditController::class, 'index'])->name('quick-audit.index');
    Route::post('/quick-audit', [QuickAuditController::class, 'store'])->name('quick-audit.store');
    Route::get('/quick-audit/{quickAudit}', [QuickAuditController::class, 'show'])->name('quick-audit.show');
    Route::delete('/quick-audit/{quickAudit}', [QuickAuditController::class, 'destroy'])->name('quick-audit.destroy');

    // Regulatory Documents Management
    Route::get('/regulatory', [RegulatoryDocumentController::class, 'index'])->name('regulatory.index');
    Route::get('/regulatory/create', [RegulatoryDocumentController::class, 'create'])->name('regulatory.create');
    Route::post('/regulatory', [RegulatoryDocumentController::class, 'store'])->name('regulatory.store');
    Route::delete('/regulatory/{document}', [RegulatoryDocumentController::class, 'destroy'])->name('regulatory.destroy');
    Route::post('/regulatory/{document}/reingest', [RegulatoryDocumentController::class, 'reingest'])->name('regulatory.reingest');
    Route::post('/regulatory/{document}/toggle-active', [RegulatoryDocumentController::class, 'toggleActive'])->name('regulatory.toggle-active');
    Route::get('/regulatory/{document}/chunks', [RegulatoryDocumentController::class, 'chunks'])->name('regulatory.chunks');
});
