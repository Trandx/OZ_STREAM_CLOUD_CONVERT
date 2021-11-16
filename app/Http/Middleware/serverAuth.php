<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class serverAuth extends ResponseController
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
        $data = $request->only('clientServerToken');

        $fields = [
            //'phone' => 'required|integer|unique:user',
            'clientServerToken' => 'required|string|min:4',
        ];

        $validator = Validator::make($data,$fields);

        if ($validator->fails()) {

            return $this->errorResponse('unauthorized', $validator->errors(), Response::HTTP_UNAUTHORIZED);
        }

        if($data['clientServerToken'] != env('CLIENT_SERVER_TOKEN'))
            return $this->errorResponse('unauthorized', null, Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
