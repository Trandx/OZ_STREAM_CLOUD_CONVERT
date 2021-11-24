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
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;


class FfmpegController extends ResponseController
{

     /**
     * @OA\Post(
     *      path="/api/upload/media",
     *      operationId="uploadMedia",
     *      tags={"Upload Media"},
     *      summary="Upload the media",
     *      description="return an url",
     *     @OA\RequestBody(required=true,@OA\JsonContent(),
     *                  @OA\MediaType(
     *               mediaType="multipart/form-data",
     *               @OA\Schema(
     *                  type="object",
     *                  required={"media"},
     *                  @OA\Property(property="media_id", type="string"),
     *                  @OA\Property(property="serie_id", type="string"),
     *                  @OA\Property(property="isFilmBande", type="string"),
     *                  @OA\Property(property="media", type="file"),
     *               ),
     *           ),
     *
     * ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bearerToken = $request->bearerToken();


        $datas = $request->only( 'media','media_id','saison_id', 'isFilmBande');

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

             $path = "users/".$path;

             $medias[$column] = env("PUBLIC_APP_URL")."/".$path;

              // contacter le server distant

                if (isset($datas['media_id'])) {

                    $medias['media_id'] = $datas['media_id'];

                    $serverDatas = [
                        'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                        'id' => $medias['media_id'],
                        'table' =>'medias',
                    ];

                    if($datas['isFilmBande']){

                        $serverDatas['link'] = $medias[$column];

                    }else{

                        $serverDatas['bandeLink'] = $medias[$column];
                    }

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/sever/add/media/link', $serverDatas);

                    $data = Media::where('media_id', $medias['media_id'])->first();

                    if($data and !is_null($data->pseudoLink)){

                        $path = explode('users/',$data->pseudoLink)[1];

                        $file_path = public_path('users/'.$path); // app_path("public/test.txt");

                       if(File::exists($file_path)) File::delete($file_path);
       
                        //unlink($user[$column]);
                        
                    }

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

                    $data = Media::where('saison_id',$medias['saison_id'])->first();

                    if($data and !is_null($data->pseudoLink)){

                        $path = explode('users/',$data->pseudoLink)[1];

                        $file_path = public_path('users/'.$path); // app_path("public/test.txt");
                       if(File::exists($file_path)) File::delete($file_path);
       
                        //unlink($user[$column]);
       
                    }

                }


            if( $response->successful() ){

                if($data){

                    $data->update($medias);

                   // $data = (object) array_merge((array) $data, (array)$medias);

                    $data->save();

                }else{
                    $data = Media::create($medias);
                }

                

                   /// lancer la job de covertion

                  /// ConverMediasJob::dispatch(public_path($path), )->delay(now()->addSeconds(60));

                return  $this->successResponse($data, ['success' => 'account ' . $id. ' is a diffuser'], Response::HTTP_CREATED);

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
    public function generateExpiredLink()
    {
       
       
          $url = URL::temporarySignedRoute('getLink', now()->addSeconds(30), ['media_id' => 1]);
            
          return $this->successResponse('success', $url, 200);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getLink(Request $request)
    {
        if (! $request->hasValidSignature()) {
            return $this->errorResponse("invalid", ['error' => 'INVALID URL'], Response::HTTP_NOT_FOUND);
        }
        $media_id = $request->media_id;
        $media = Media::find($media_id);
        $to = 'user/'. explode('users/',$media->pseudoLink)[1];
        //echo $to;

        //return Redirect::to($media->pseudoLink)->withInput(['id'=>1]);

        //return $this->successResponse('success', ['url' =>$media->pseudoLink], 200);
        return static::inline($to);

        
    }

    public static function inline($path, $name = null, $lifetime = 0)
    {
        $mineType = Storage::mimeType($path);
        $path = Storage::path($path);
        
       
        if (is_null($name)) {
            $name = basename($path);
        }

        $filetime = filemtime($path);
        $etag = md5($filetime . $path);
        $time = gmdate('r', $filetime);
        $expires = gmdate('r', $filetime + $lifetime);
        $length = filesize($path);

        $headers = array(
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Last-Modified' => $time,
            'Cache-Control' => 'must-revalidate',
            'Expires' => $expires,
            'Pragma' => 'public',
            'Etag' => $etag,
        );

        $headerTest1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $time;
        $headerTest2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag;
        
        if ($headerTest1 || $headerTest2) { //image is cached by the browser, we dont need to send it again
            return response('', 304, $headers);
        }

        

        $headers = array_merge($headers, array(
            'Content-Type' => $mineType,
            'Content-Length' => $length,
                ));

        return response(Storage::get($path), 200, $headers);


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
