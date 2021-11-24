<?php

use App\Http\Controllers\FfmpegController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;
use Illuminate\Support\Facades\File;

Route::middleware(['validMedia'])->group(function () {

    Route::post('/upload/media',[Api\FfmpegController::class, 'index']);

});

Route::get('/getLink',[Api\FfmpegController::class, 'generateExpiredLink'])->name('getExpiredLink');

Route::get('/link',[Api\FfmpegController::class, 'getLink'])->name('getLink');

Route::get('medias/{file}', function($file)
{   

    $path =  public_path('users').'/1/medias/' . $file; 
    
    if (File::exists($path)) {
        return Api\FfmpegController::inline($path);
    }
    return response(null, 404);
});

Route::get('medias{folder1??}/{file}', function($folder1,$file)
{   
echo 'test';
    $path =  public_path('users').'/1/medias/'.$folder1/*.($folder2??'/'.$folder2).($folder3??'/'.$folder3).*/.'/' . $file; 
    
    if (File::exists($path)) {
        return Api\FfmpegController::inline($path);
    }
    return response(null, 404);
});

//Route::get('/convertToMkv',[FfmpegController::class, 'convertToMkv'])->name('getLink');
