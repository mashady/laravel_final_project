<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\ReviewController;

use App\Http\Controllers\StudentProfileController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//These are registration routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


//Owner Profile routes
Route::middleware('auth:sanctum')->group(function () {
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




// Student Profile Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get all student profiles
    Route::get('/student-profiles', [StudentProfileController::class, 'index']);
    
    // Create student profile
    Route::post('/student-profiles', [StudentProfileController::class, 'store']);
    
    // Get specific student profile
    Route::get('/student-profiles/{id}', [StudentProfileController::class, 'show']);
    
    // Update student profile
    Route::put('/student-profiles', [StudentProfileController::class, 'update']);
    
    // Delete student profile
    Route::delete('/student-profiles/{id}', [StudentProfileController::class, 'destroy']);
    
    // Get authenticated user's profile
    Route::get('/my-profile', [StudentProfileController::class, 'myProfile']);
    
    // Check if user has a profile
    Route::get('/has-profile', [StudentProfileController::class, 'hasProfile']);
    
    // Get profile completion status
    Route::get('/profile-completion', [StudentProfileController::class, 'profileCompletion']);
    
    // Update profile picture only
    Route::post('/update-picture', [StudentProfileController::class, 'updatePicture']);
    
    // Remove profile picture
    Route::delete('/remove-picture', [StudentProfileController::class, 'removePicture']);
});

// Search profiles by university (public route)
Route::get('/search-by-university', [StudentProfileController::class, 'searchByUniversity']);