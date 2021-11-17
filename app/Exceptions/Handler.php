<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Validation\ValidationException;
use Swift_TransportException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function render($request, Throwable $exception){

        $data = ['success' => false,
        'message' => $exception->getMessage(),
       ];

        //var_dump($data);

           if ($exception instanceof MethodNotAllowedHttpException) {
               return response()->json($data, 405);

           }

           if($request->expectsJson()){

               if($exception instanceof ValidationException){

                   $data[ 'error'] = $exception->validator->errors();

                   return response()->json($data, 422);
               }

               /*if ($exception instanceof NotFoundHttpException) {
                   return response()->json(['message' => 'this url is not founded', 'errors' => 'not found'], 404);
               }*/
               /*elseif ($exception instanceof DatabaseQueryException) {
                   return response()->json(['message' => 'parameters don\'t matched with the data base', 'errors' => 'Fatal error'], 500);
               }*/
               if ($exception instanceof PostTooLargeException) {

                  return response()->json( $data, 413);

               }
               if ($exception instanceof RequestException) {
                   return response()->json( $data, 500);
               }


               if ($exception instanceof RouteNotFoundException) {
                   return response()->json( $data, 404);
               }

               if($exception instanceof NotFoundHttpException){
                   $data[ 'message'] = 'Url not found';
                   return response()->json( $data, 404);

               }

               if($exception instanceof QueryException){
                   return response()->json( $data, 500);

               }

              /* return response()->json([
                   'message' => $exception->getMessage(),
                   'error' => 'Unknow error'
               ], 500);*/

               if($exception instanceof Swift_TransportException){
                   return response()->json( $data, 404);

               }
           }

       return parent::render($request, $exception);
   }
}
