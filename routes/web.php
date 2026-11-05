<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CopilotController;
use App\Http\Controllers\Admin\CopilotPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
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

    // Dedicated Statutory AI Copilot Workspace
    Route::get('/copilot', [CopilotPageController::class, 'index'])->name('copilot.index');

    // Administrative Floating Copilot
    Route::post('/copilot/ask', CopilotController::class)->name('copilot');

    // Multi-Block Projects, Blocks & Drawings Compliance Workspace
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [AdminProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [AdminProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [AdminProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [AdminProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/blocks', [AdminProjectController::class, 'storeBlock'])->name('projects.blocks.store');
    Route::put('/projects/{project}/blocks/{block}', [AdminProjectController::class, 'updateBlock'])->name('projects.blocks.update');
    Route::delete('/projects/{project}/blocks/{block}', [AdminProjectController::class, 'destroyBlock'])->name('projects.blocks.destroy');
    Route::post('/projects/{project}/blocks/{block}/drawings', [AdminProjectController::class, 'uploadDrawing'])->name('projects.drawings.upload');
    Route::get('/projects/{project}/report', [AdminProjectController::class, 'report'])->name('projects.report');

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
