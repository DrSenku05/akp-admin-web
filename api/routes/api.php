<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AdminWorkflowController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'service' => 'akp-api']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::apiResource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);

Route::prefix('admin')->middleware('admin.token')->controller(AdminWorkflowController::class)->group(function () {
    Route::get('reports/summary', 'report');
    Route::get('reports/summary/export', 'reportExport');
    Route::get('reports/summary/pdf', 'reportPdf');
    Route::post('backups/create', 'createBackup');
    Route::post('backups/{id}/restore', 'restoreBackup');
    Route::get('{resource}/export', 'export');
    Route::get('{resource}', 'index');
    Route::post('{resource}', 'store');
    Route::match(['put', 'patch'], '{resource}/{id}', 'update');
    Route::post('{resource}/{id}/{action}', 'action');
});
