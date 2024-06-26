<?php

use App\Http\Controllers\Api\v1\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix('v1')->middleware(['auth:api', 'throttle:60,1'])->group(function (): void {
    Route::get('payment/summary', [PaymentController::class, 'summary']);
    Route::apiResource('payment', PaymentController::class);
});
