<?php

use App\Http\Controllers\FfmpegController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;

Route::middleware(['validMedia'])->group(function () {

    Route::post('/upload/media',[Api\FfmpegController::class, 'index']);

    Route::post('/convertToM3u8',[Api\FfmpegController::class, 'index']);

    Route::get('/convertToMkv',[FfmpegController::class, 'convertToMkv']);
});
