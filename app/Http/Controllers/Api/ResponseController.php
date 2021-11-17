<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    protected function errorResponse($errorMessages, $dataError = [], $status){
        $response = [
            'success' => false,
            'message' => $errorMessages,
        ];
        if(!empty($dataError)){
            $response['data'] = $dataError;
        }
        return response()->json($response, $status, [], JSON_NUMERIC_CHECK);
    }


    protected function successResponse($result, $message, $status = 200, $success = true){
        $response = [
            'success' => $success,
            'data'    => $result,
            'message' => $message,
        ];
        return response()->json($response, $status, [], JSON_NUMERIC_CHECK);
    }
}
