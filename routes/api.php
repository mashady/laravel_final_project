<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;

use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PaymentController;



Route::get('/user', function (Request $request) {
    $user = $request->user();
    if ($user->role === 'owner') {
        $user->load(['ownerProfile', 'ads']);
    } elseif ($user->role === 'student') {
        $user->load('studentProfile');
    }

    return response()->json($user);
})->middleware('auth:sanctum');

//These are registration routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']);
});

//Owner Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ads', [AdController::class, 'store']);
    Route::get('/myProperties', [AdController::class, 'userAds']);

    Route::post('/users/{user}/update-with-profile', [UserController::class, 'updateWithProfile'])
        ->name('users.updateWithProfile');
    Route::get('/owners', [OwnerController::class, 'index']);
    Route::get('/oneowner/{id}', [OwnerController::class, 'show']);
    Route::post('/createowner', [OwnerController::class, 'store']);
    Route::post('/updateowner', [OwnerController::class, 'update']);
    Route::delete('/deleteowner/{id}', [OwnerController::class, 'destroy']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle/{ad}', [WishlistController::class, 'toggle']);
    Route::get('/wishlist/check/{ad}', [WishlistController::class, 'check']);

});
// User Routes
Route::post('/users/{id}/update', [UserController::class, 'update']);
Route::apiResource('users', UserController::class);

// Review Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/ads/{adId}/reviews', [ReviewController::class, 'forAd']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

// ad routes 
Route::get('/ads', [AdController::class, 'index']);
Route::get('/ads/{ad}', [AdController::class, 'show']);
Route::put('/ads/{ad}', [AdController::class, 'update']);
Route::delete('/ads/{ad}', [AdController::class, 'destroy']);

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



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/{user}', [ChatController::class, 'getMessages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans/subscribe', [PlanController::class, 'subscribeToPlan']);
    Route::get('/plans/my-subscription', [PlanController::class, 'mySubscription']);
    Route::post('/plans/cancel-subscription', [PlanController::class, 'cancelSubscription']);
    Route::put('/plans/{id}/upgrade-subscribe', [PlanController::class, 'upgradeSubscription']);
    Route::post('/plans/{id}/re-subscribe', [PlanController::class, 'reSubscribeToPlan']);
    Route::get('/plans/mycart', [PlanController::class, 'viewMYCart']);
    Route::post('/plans/add-to-cart', [PlanController::class, 'addToCart']);
    Route::post('/plans/remove-from-cart', [PlanController::class, 'removeFromCart']);

});


Route::get('/user-data/{id}', [UserController::class, 'showWithProfile']);


Route::post('/create-checkout-session', [PaymentController::class, 'createSession']);
Route::post('/add-to-payment', [PaymentController::class, 'addToPayment'])->middleware('auth:sanctum');