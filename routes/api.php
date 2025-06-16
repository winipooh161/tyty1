<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TemplateStatusController;
use App\Http\Controllers\Api\QrVerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршрут для изменения статуса шаблона
Route::post('/change-template-status', [TemplateStatusController::class, 'changeStatus'])
    ->middleware('auth:sanctum');

// Маршрут для получения подписанного URL (с поддержкой сессионной аутентификации)
Route::get('/get-signed-status-url/{template}/{user}/{acquired}/{timestamp}', [TemplateStatusController::class, 'getSignedStatusUrl'])
    ->middleware('web', 'auth');

// Маршрут для проверки прав доступа к QR-коду шаблона
Route::post('/verify-qr-access', [QrVerificationController::class, 'verifyAccess'])
    ->middleware('auth:sanctum');
