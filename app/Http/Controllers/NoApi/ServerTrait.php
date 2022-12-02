<?php

namespace App\Http\Controllers\NoApi;

use Illuminate\Support\Facades\Http;

/**
 * gestion de request server
 */
trait ServerTrait
{

    public static function postServer($url, $bearerToken, array $serverDatas = [] ){

        $url = env('OZ_STREAM_SERVER').$url;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json',
        ])->withOptions(["verify"=>false])->post($url, $serverDatas);

        return $response;
    }
    
}
