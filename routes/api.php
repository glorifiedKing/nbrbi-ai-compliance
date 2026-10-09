<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlockFloorController;
use App\Http\Controllers\Api\CopilotController;
use App\Http\Controllers\Api\DrawingController;
use App\Http\Controllers\Api\InternalWebhookController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Microservice Webhooks
|--------------------------------------------------------------------------
*/
Route::post('/internal/compliance-webhook', [InternalWebhookController::class, 'handleComplianceWebhook'])
    ->name('api.internal.compliance-webhook');

/*
|--------------------------------------------------------------------------
| Mobile Client API (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function (): void {

    // Authentication
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/profile', [AuthController::class, 'profile'])->name('api.v1.auth.profile');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Projects
        Route::get('/projects', [ProjectController::class, 'index'])->name('api.v1.projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('api.v1.projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('api.v1.projects.show');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('api.v1.projects.update');
        Route::post('/projects/join', [ProjectController::class, 'joinByToken'])->name('api.v1.projects.join');
        Route::post('/projects/{project}/collaborators', [ProjectController::class, 'addCollaborator'])->name('api.v1.projects.collaborators');

        // Blocks & Floors
        Route::post('/projects/{project}/blocks', [BlockFloorController::class, 'storeBlock'])->name('api.v1.blocks.store');
        Route::post('/blocks/{block}/floors', [BlockFloorController::class, 'storeFloor'])->name('api.v1.floors.store');
        Route::post('/blocks/{block}/floors/batch', [BlockFloorController::class, 'batchCreateFloors'])->name('api.v1.floors.batch');
        Route::delete('/floors/{floor}', [BlockFloorController::class, 'destroyFloor'])->name('api.v1.floors.destroy');

        // Drawings & Compliance
        Route::post('/drawings/upload', [DrawingController::class, 'upload'])->name('api.v1.drawings.upload');
        Route::get('/drawings/{drawing}/versions', [DrawingController::class, 'versionHistory'])->name('api.v1.drawings.versions');
        Route::get('/drawings/versions/{version}/report', [DrawingController::class, 'complianceReport'])->name('api.v1.drawings.report');

        // Consolidated Project Audit Report
        Route::get('/projects/{project}/report', [ReportController::class, 'projectReport'])->name('api.v1.projects.report');

        // Floating AI Copilot
        Route::post('/copilot/ask', [CopilotController::class, 'ask'])->name('api.v1.copilot.ask');
    });
});
