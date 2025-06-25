<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (Request $request, $token) {
    $email = $request->query('email');
    return Redirect::away(config('app.frontend_url') . "/reset-password?token=$token&email=$email");
})->name('password.reset');

