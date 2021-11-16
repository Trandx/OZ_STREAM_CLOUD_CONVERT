<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ValidMedia extends ResponseController
{
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

        $datas = $request->only('media_id', 'saison_id');

        $field = [
            'media_id' => 'required_without:saison_id',
            'saison_id' => 'required_without:media_id',
        ];

        $validator = Validator::make($datas,$field);

        if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

         if(isset($datas['media_id'])){ $params['media_id'] = $datas['media_id'];}

         if(isset($datas['saison_id'])){ $params['saison_id'] = $datas['saison_id'];}

         $params['clientServerToken'] = env('CLIENT_SERVER_TOKEN');

        $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/server/is/media/diffuser', $params);

        if( $response->successful() ){

            // if(User::where('accessToken',$datas['accessToken'])->first()){
            //     return $next($request);
            // }
            $id = $response->json()['data']['id'];

            $request->request->add(['id' => $id]);

            return $next($request);

            // return  response()->json($response->json(), Response::HTTP_UNAUTHORIZED);

        }elseif( $response->failed() ){

            return  response()->json($response->json(), Response::HTTP_UNAUTHORIZED);;

        }

    }
}
