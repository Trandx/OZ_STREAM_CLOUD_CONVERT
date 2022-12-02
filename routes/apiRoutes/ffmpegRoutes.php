<?php

use App\Http\Controllers\FfmpegController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;
use Illuminate\Support\Facades\File;

// Route::middleware(['validMedia'])->group(function () {

//     Route::post('/upload/media',[Api\FfmpegController::class, 'index']);

// });

// Route::get('/getLink',[Api\FfmpegController::class, 'generateExpiredLink'])->name('getExpiredLink');

// Route::get('getMediaBandeData/{media_id}',[Api\FfmpegController::class, 'getMediaBandeData'])->name('getMediaBandeData');

// Route::get('getSaisonBandeData/{media_id}',[Api\FfmpegController::class, 'getSaisonBandeData'])->name('getSaisonBandeData');

// Route::get('getMediaData/{media_id}',[Api\FfmpegController::class, 'getMediaData'])->name('getMediaData');

// Route::get('getKey/{key}', [Api\FfmpegController::class, 'getKey'])->name('getKey');

// Route::get('playlist/{media_id}/{playlist}', [Api\FfmpegController::class, 'playlist'])->name('playlist');

// Route::get('convert',[Api\ConvertController::class, 'convert'])->name('convert');

/////////// upload file /////

Route::middleware(['validMedia'])->group(function () {

    Route::post('/upload/media',[Api\Upload\UploadController::class, 'store'])->name('upload');

});

Route::get('getMediaBandeData/{media_id}',[Api\Upload\UploadController::class, 'getMediaBandeData'])->name('getMediaBandeData');

Route::get('getSaisonBandeData/{media_id}',[Api\Upload\UploadController::class, 'getSaisonBandeData'])->name('getSaisonBandeData');

Route::get('getMediaData/{media_id}',[Api\Upload\UploadController::class, 'getMediaData'])->name('getMediaData');

Route::get('getMediaFormat/{media_id}',[Api\Upload\UploadController::class, 'getMediaFormat'])->name('getMediaFormat');


Route::get('/link',[Api\FfmpegController::class, 'getLink'])->name('getLink');

Route::get('medias/{file}', function($file)
{   

    $path =  public_path('users').'/1/medias/' . $file; 
    
    if (File::exists($path)) {
        return (new Api\FfmpegController())->inline($path);
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
