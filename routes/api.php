<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//These are registration routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

<<<<<<< HEAD

//These are review routes   
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/owners/{ownerId}/reviews', [ReviewController::class, 'forOwner']);
     Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});
=======
// USer Routes
Route::post('/users/{id}/update', [UserController::class, 'update']);
Route::apiResource('users', UserController::class);




// ad routes 
Route::apiResource('/ads', AdController::class);


>>>>>>> 499bdd6f7bc0979ff29feec5adc4988ab382d1ec
