<?php

namespace App\Http\Middleware;


use App\Http\Controllers\Api\Auth\SessionTrait;
use App\Http\Controllers\Api\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ValidAccount extends ResponseController
{
    use SessionTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();

        $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/server/is/validuser', [
            'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
        ]);

        if( $response->successful() ){

            // if(User::where('accessToken',$datas['accessToken'])->first()){
            //     return $next($request);
            // }

            return $next($request);

            // return  response()->json($response->json(), Response::HTTP_UNAUTHORIZED);

        }elseif( $response->failed() ){

            return  response()->json($response->json(), Response::HTTP_UNAUTHORIZED);;

        }

    }
}
