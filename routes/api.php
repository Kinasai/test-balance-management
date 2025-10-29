<?php

use App\Http\Controllers\BalanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

    // Получение баланса
    Route::get('/balance/{user_id}', [BalanceController::class, 'getBalance']);

    // Начисление средств
    Route::post('/deposit', [BalanceController::class, 'deposit']);

    // Списание средств
    Route::post('/withdraw', [BalanceController::class, 'withdraw']);

    // Перевод между пользователями
    Route::post('/transfer', [BalanceController::class, 'transfer']);
