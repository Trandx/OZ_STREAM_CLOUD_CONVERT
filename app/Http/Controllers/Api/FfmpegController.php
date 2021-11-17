<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\SessionTrait;
use App\Http\Controllers\FfmpegController as Ffmpeg;
use App\Http\Controllers\NoApi\FileUploadController;
use App\Jobs\ConverMediasJob;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FfmpegController extends ResponseController
{
    use SessionTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bearerToken = $request->bearerToken();


        $datas = $request->only( 'media','media_id','saison_id');

       $field = [
            'media' => 'required|file|max:1024000' /*|mimes:mp4,mkv,ts|dimensions:width=500,height=500',*/,
        ];

        $validator = Validator::make($datas,$field);

       if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $id = $request->id;


        $data =  (new FileUploadController())->store($request,  $id."/medias");
       // var_dump($datas);

            $path = $data[0]['file_path'];
            //var_dump($path);
            //$data = array_merge([$type.'Link'=> "users/".$path], self::updated_at(), self::updated_by());


             $column = 'pseudoLink';

             if(isset($medias[$column]) and !is_null($medias[$column])){

                 $file_path = app_path($medias[$column]); // app_path("public/test.txt");
                if(File::exists($file_path)) File::delete($file_path);

                 //unlink($user[$column]);

             }
             $path = "users/".$path;

             $medias[$column] = env("PUBLIC_APP_URL")."/".$path;

              // contacter le server distant

                if (isset($datas['media_id'])) {

                    $medias['media_id'] = $datas['media_id'];

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/sever/add/media/link', [
                        'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                        'link' => $medias[$column],
                        'id' => $medias['media_id'],
                        'table' =>'medias',
                    ]);

                }

                if (isset($datas['saison_id'])) {

                    $medias['saison_id'] = $datas['saison_id'];

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/sever/add/media/link', [
                        'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                        'bandeLink' => $medias[$column],
                        'id' => $medias['saison_id'],
                        'user_id' => $medias['user_id'],
                        'table' =>'saisons',
                    ]);

                }


            if( $response->successful() ){

                $medias = Media::create($medias);

                   /// lancer la job de covertion

                  /// ConverMediasJob::dispatch(public_path($path), )->delay(now()->addSeconds(60));

                return  $this->successResponse($medias, ['success' => 'account ' . $id. ' is a diffuser'], Response::HTTP_CREATED);

            }elseif( $response->failed() ){

                return  $this->errorResponse($response->json(), ['error' => 'request error'], $response->status());

            }





        return $this->errorResponse('error',
                ['error' => 'the field convertType is required'], 404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
