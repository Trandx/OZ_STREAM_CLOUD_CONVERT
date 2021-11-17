<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;


Route::middleware(["validAccount"])->group(function(){
    Route::put('users/pay', [PaymentController::class, 'credit'])->name('oz-stream.user.credit');
    Route::put('users/creditfor', [PaymentController::class, 'creditFor'])->name('oz-stream.user.creditfor');
});
