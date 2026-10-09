<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegulatoryDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Regulatory Documents Management
    Route::get('/regulatory', [RegulatoryDocumentController::class, 'index'])->name('regulatory.index');
    Route::get('/regulatory/create', [RegulatoryDocumentController::class, 'create'])->name('regulatory.create');
    Route::post('/regulatory', [RegulatoryDocumentController::class, 'store'])->name('regulatory.store');
    Route::delete('/regulatory/{document}', [RegulatoryDocumentController::class, 'destroy'])->name('regulatory.destroy');
    Route::post('/regulatory/{document}/reingest', [RegulatoryDocumentController::class, 'reingest'])->name('regulatory.reingest');
    Route::post('/regulatory/{document}/toggle-active', [RegulatoryDocumentController::class, 'toggleActive'])->name('regulatory.toggle-active');
    Route::get('/regulatory/{document}/chunks', [RegulatoryDocumentController::class, 'chunks'])->name('regulatory.chunks');
});
