<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;

Route::post('opendrive/login',  [Api\OpenDriverController::class, 'opd_login'])->name('oz-stream.open-drive.login');

Route::post('opendrive/upload',  [Api\OpenDriverController::class, 'opd_uploadFile'])->name('oz-stream.open-drive.upload');

Route::get('opendrive/getfolderandfilelist',  [Api\OpenDriverController::class, 'opd_getFolderAndFileList'])->name('oz-stream.open-drive.folder-list');

Route::get('opendrive/createfolder',  [Api\OpenDriverController::class, 'opd_createFolder'])->name('oz-stream.open-drive.folder-list');


?>