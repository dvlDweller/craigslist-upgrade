<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/{listing}', [ListingController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:registration');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login');
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
        Route::get('/user', [AuthController::class, 'user'])
            ->middleware('auth:sanctum');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
