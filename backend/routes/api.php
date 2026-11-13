<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\SellerListingController;
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

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/seller/listings', [SellerListingController::class, 'index']);
    Route::post('/seller/listings', [SellerListingController::class, 'store']);
    Route::get('/seller/listings/{listing}', [SellerListingController::class, 'show']);
    Route::patch('/seller/listings/{listing}', [SellerListingController::class, 'update']);
    Route::delete('/seller/listings/{listing}', [SellerListingController::class, 'destroy']);
    Route::post('/seller/listings/{listing}/publish', [SellerListingController::class, 'publish']);
    Route::post('/seller/listings/{listing}/mark-sold', [SellerListingController::class, 'markSold']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
