<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//These are registration routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


//Owner Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/oneowner/{id}', [OwnerController::class, 'show']);
    Route::post('/createowner', [OwnerController::class, 'store']);
    Route::post('/updateowner', [OwnerController::class, 'update']);
    Route::delete('/deleteowner/{id}', [OwnerController::class, 'destroy']);
});

