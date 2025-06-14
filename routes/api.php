<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\ReviewController;

use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\BookingController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//These are registration routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']);
});

//Owner Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/users/{user}/update-with-profile', [UserController::class, 'updateWithProfile'])
        ->name('users.updateWithProfile');
    Route::get('/owners', [OwnerController::class, 'index']);
    Route::get('/oneowner/{id}', [OwnerController::class, 'show']);
    Route::post('/createowner', [OwnerController::class, 'store']);
    Route::post('/updateowner', [OwnerController::class, 'update']);
    Route::delete('/deleteowner/{id}', [OwnerController::class, 'destroy']);

});
// USer Routes
Route::post('/users/{id}/update', [UserController::class, 'update']);
Route::apiResource('users', UserController::class);

// Review Routes
Route::post('/reviews', [ReviewController::class, 'store']);
Route::get('/owners/{ownerId}/reviews', [ReviewController::class, 'forOwner']);
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

// ad routes 
Route::apiResource('/ads', AdController::class);

// Booking Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::get('/bookings', [BookingController::class, 'allBookings']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::patch('/bookings/{id}/payment', [BookingController::class, 'updatePayment']);
});



// Student Profile Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/student-profile/has-profile', [StudentProfileController::class, 'hasProfile']);
    Route::get('/student-profile/completion', [StudentProfileController::class, 'profileCompletion']);
    Route::post('/student-profile/complete-step', [StudentProfileController::class, 'completeProfileStep']);
    Route::get('/student-profile/my-profile', [StudentProfileController::class, 'myProfile']);
    Route::post('/student-profile/bulk-update', [StudentProfileController::class, 'bulkUpdate']);
    Route::post('/student-profile/update-picture', [StudentProfileController::class, 'updatePicture']);
    Route::delete('/student-profile/remove-picture', [StudentProfileController::class, 'removePicture']);
    Route::get('/student-profile/search-university', [StudentProfileController::class, 'searchByUniversity']);
    Route::get('/student-profile/user/{userId}', [StudentProfileController::class, 'getProfileByUserId']);
    Route::get('/student-profile/stats', [StudentProfileController::class, 'profileStats']);
    Route::apiResource('student-profile', StudentProfileController::class);
});

Route::get('/student-profile/{studentProfile}/public', [StudentProfileController::class, 'show']);
Route::get('/student-profile/public/search-university', [StudentProfileController::class, 'searchByUniversity']);
Route::get('/user-data/{id}', [UserController::class, 'showWithProfile']);