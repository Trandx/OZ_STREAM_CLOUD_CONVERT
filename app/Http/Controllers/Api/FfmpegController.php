<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\NoApi\FileUploadController;
use App\Http\Controllers\NoApi\ServerTrait;
use App\Jobs\ConverMediasJob;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;


class FfmpegController extends ResponseController
{
   use ServerTrait;

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
     *                  @OA\Property(property="saison_id", type="string"),
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
            'media' => 'required|file|mimes:mp4,flv,3gp,mov,avi,webm,wmv|max:1024000' /*|mimes:mp4,mkv,ts|dimensions:width=500,height=500',*/,
        ];

        $validator = Validator::make($datas,$field);

       if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $id = $request->id;


        $data =  (new FileUploadController())->mediaStore($request,  $id."/medias");
       // var_dump($datas);

            $path = $data[0]['file_path'];
            //var_dump($path);
            //$data = array_merge([$type.'Link'=> "users/".$path], self::updated_at(), self::updated_by());

             $path = "users/".$path;

              // contacter le server distant

                if (isset($datas['media_id'])) {

                    $medias['media_id'] = $datas['media_id'];

                    $serverDatas = [
                        'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                        'id' => $medias['media_id'],
                        'table' =>'medias',
                    ];
                    
                    $mediaInfo = Media::where('media_id', $medias['media_id'])->first();

                    if(!$datas['isFilmBande']){

                        $column = 'mediaPath';

                        $medias[$column] = $path; //env("PUBLIC_APP_URL")."/".$path;

                        $link = 'api/getMediaData/'.$medias["media_id"];

                        $serverDatas['link'] =  $link; // $medias[$column]

                        /// info sur la video
                        $mediaData =  (new ConvertController())->analyse($path);

                        $serverDatas['duration'] =  $mediaData->duration; // $medias[$column]

                        $oldPath = $mediaInfo->mediaPath??null;

                         //lance le generateur de pseudolien

                    //$expiredLink = $this->generateExpiredLink('getMediaData',now()->addHours(5), ['media_id' => $medias['media_id'], 'token' => $bearerToken]);
                    
                    
                }else{
                        ///il faut fourmir le lien final.
                        $column = 'bandePath';

                        $medias[$column] = $path; //env("PUBLIC_APP_URL")."/".$path;

                        //penser à mettre à jour la durée de la vidéo

                        $link = 'api/getMediaBandeData/'.$medias["media_id"];

                        $serverDatas['bandeLink'] =  $link; // $medias[$column]

                        $oldPath = $mediaInfo->bandePath??null;

                    }

                    $response = static::postServer('/api/sever/add/media/link', $bearerToken, $serverDatas);
                   
                  /*  $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/sever/add/media/link', $serverDatas);
                    */

                }

                if (isset($datas['saison_id'])) {

                    $link = 'api/getSaisonBandeData/'.$datas["saison_id"];

                    $serverDatas = [
                        'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                        'id' => $datas['saison_id'],
                        'table' =>'saisons',
                        'bandeLink' =>  $link,
                    ];

                    $medias['saison_id'] = $datas['saison_id'];

                    $column = 'bandePath';

                    $medias[$column] = $path; //env("PUBLIC_APP_URL")."/".$path;

                    $response = static::postServer('/api/sever/add/media/link', $bearerToken, $serverDatas);

                   /* $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$bearerToken,
                        'Accept' => 'application/json',
                    ])->post(env('OZ_STREAM_SERVER').'/api/sever/add/media/link',  $serverDatas );*/
                    $mediaInfo = Media::where('saison_id', $datas['saison_id'])->first();

                    $oldPath = $mediaInfo->bandePath??null;
                   
                }



            if( $response->successful() ){

                if(File::exists($oldPath)){

                   // $oldPath = public_path($oldPath);
                    File::delete($oldPath);

                }elseif(Storage::exists($oldPath)){

                   // $oldPath = storage_path($oldPath);
                    Storage::delete($oldPath);

                }elseif(!is_null($oldPath) ){
                    return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
                }

               
               
                if($mediaInfo){

                    $mediaInfo->update($medias);

                   // $mediaInfo = (object) array_merge((array) $mediaInfo, (array)$medias);

                    $mediaInfo->save();

                }else{
                    $mediaInfo = Media::create($medias);
                }

                //$mediaInfo->temporalyLink = $expiredLink;
                    $data = (object)[];
                
                    $data->link = env('PUBLIC_APP_URL').'/'.$link;
              

                   /// lancer la job de covertion

                  /// ConverMediasJob::dispatch(public_path($path), )->delay(now()->addSeconds(60));

                return  $this->successResponse($data, ['success' => 'this user can read'], Response::HTTP_CREATED);

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
    public function generateExpiredLink($route, $expiredTime, $datas)
    {
       
          return URL::temporarySignedRoute($route, $expiredTime, $datas);
            
          //return $this->successResponse('success', $url, 200);
        
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
        return $this->inline($to);

    }

      /**
     * @OA\Post(
     *      path="/api/getMediaBandeData/{media_id}",
     *      operationId="streamming",
     *      tags={"media streamming"},
     *      summary="Uget datas streams",
     *      description="return the content",
     * @OA\Parameter(name="media_id",description="id of media",in="path", @OA\Schema(type="string" )),
     *     
     *      @OA\Response(
     *          response=20110,
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

    public function getMediaBandeData($media_id){

        $media = Media::where('media_id',$media_id)->first();

        $path = $media->bandePath;

        return $this->inline($path);

    }

          /**
     * @OA\Post(
     *      path="/api/getMediaData/{media_id}",
     *      operationId="streamming",
     *      tags={"media streamming"},
     *      summary="Uget datas streams",
     *      description="return the content",
     * @OA\Parameter(name="media_id",description="id of media",in="path", @OA\Schema(type="string" )),
     *     
     *      @OA\Response(
     *          response=20110,
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

    public function getMediaData(Request $request, $media_id=null){

        $bearerToken = $request->bearerToken();

        $media_id = $media_id??$request->media_id;

       // $media_id = $request->media_id;

        $serverDatas = [
            'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
            'media_id' => $media_id,
        ];

      /*  if (! $request->hasValidSignature()) {

            return $this->errorResponse("invalid", ['error' => 'INVALID URL'], Response::HTTP_NOT_FOUND);
        }
        */

        //appel de la function qui verifie si un user à payé
        $response = static::postServer('/api/sever/user/bought', $bearerToken, $serverDatas );

        if( $response->successful() ){

             // verifie si le finaLink n'est pas null
        
        $media = Media::where('media_id',$media_id)->first();

        if(is_null($media->finalMediaLink)){

             $path = $media->mediaPath;

            return $this->inline($path);

        }else{
            // appel de la function  de génération du m3u8

        }


        }elseif( $response->failed() ){

            return  $this->errorResponse($response->json(), ['error' => 'request error'], $response->status());

        }

       

    }

              /**
     * @OA\Post(
     *      path="/api/getSaisonBandeData/{saison_id}",
     *      operationId="streamming",
     *      tags={"media streamming"},
     *      summary="Uget datas streams",
     *      description="return the content",
     * @OA\Parameter(name="saison_id",description="id of saison",in="path", @OA\Schema(type="string" )),
     *     
     *      @OA\Response(
     *          response=20110,
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

    public function getSaisonBandeData($saison_id){
        
        $media = Media::where('saison_id',$saison_id)->first();

        $path = $media->bandePath;

        return $this->inline($path);

    }

    public function inline($path, $name = null, $lifetime = 0)
    {

        if(File::exists($path)){

            $mineType = File::mimeType($path);

            $basPath = public_path($path);

            $isFile = true;

        }elseif(Storage::exists($path)){

            $mineType = Storage::mimeType($path);
            $basPath = Storage::path($path);

        }else{
            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
        }

        
       
       
        if (is_null($name)) {
            $name = basename($path);
        }

        $filetime = filemtime($basPath);
        $etag = md5($filetime . $basPath);
        $time = gmdate('r', $filetime);
        $expires = gmdate('r', $filetime + $lifetime);
        $length = filesize($basPath);

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

        return response(isset($isFile)?File::get($path):Storage::get($path), 200, $headers);


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
