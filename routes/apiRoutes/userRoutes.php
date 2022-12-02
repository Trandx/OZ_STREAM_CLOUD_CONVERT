<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api as Api;



Route::post('register', [Api\Auth\UserController::class, 'registerUser'])->middleware('serverAuth')->name('oz-stream.registerUser');

Route::middleware(["validAccount"])->group(function () {

    Route::post('logout', [Api\Auth\LogoutController::class, 'logout'])->name('oz-stream.deconnexion');

    //refresh token user
    Route::post('refresh', [Api\Auth\RefreshController::class, 'refresh'])->name('oz-stream.refresh_token');

});
