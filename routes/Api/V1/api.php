<?php

use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('trips')->group(function () {
        Route::post('/', [TripController::class, 'store']);
        Route::get('/{trip}', [TripController::class, 'show']);
        Route::post('/{trip}/accept', [TripController::class, 'accept']);
        Route::patch('/{trip}/status', [TripController::class, 'updateStatus']);
    });

    Route::prefix('drivers')->group(function () {
        Route::get('/nearby', [DriverController::class, 'nearby']);
        Route::post('/{driver}/location', [DriverController::class, 'updateLocation']);
    });
});
