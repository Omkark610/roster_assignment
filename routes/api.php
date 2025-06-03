<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortfolioController;

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

Route::middleware('api.key')->group(function () {
    Route::post('/ingest', [PortfolioController::class, 'ingest']);
    Route::get('/profile/{username}', [PortfolioController::class, 'show']);
    Route::post('/profile/{username}', [PortfolioController::class, 'update']);
    Route::delete('/profile/{username}', [PortfolioController::class, 'destroy']);
});

Route::get('/test', function (Request $request) {
    return "test";
});