<?php

namespace App\Http\Controllers\Api\Upload;

use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\NoApi\ServerTrait;
use App\Http\Controllers\Src\AnalyseMedia;
use App\Jobs\ConvertMediasJob;
use App\Jobs\UploadMediaOnDailyMotion;
use App\Models\Medias;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use stdClass;

class UploadController extends ResponseController
{
    use ServerTrait;

    private function getResources($media){

        if( $media->is_online){

            // $path = Storage::($media->mediaPath);
            
           // $name = basename($media->bandePath);
            //dd($name);
 
            return redirect()->away($media->current_path);
 
           
 
         }else{
             // appel de la function  de génération du m3u8
 
             return $this->inline($media->current_path);
         }
    }

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
     *                  @OA\Property(property="is_film_bande", type="boolean"),
     *                  @OA\Property(property="file", type="file"),
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AnalyseMedia $mediaAnalysing)
    {
        $bearerToken = $request->bearerToken();

        $file = $request->file("file");
        $anotherFileds = $request->all();

        $field = [

            'file' => 'required|file|mimes:mp4|max:1024000',

           // "title" => 'required|string',

        ];

        $validator = Validator::make($anotherFileds, $field);

        if ($validator->fails()) {

            return $this->errorResponse('undefined',['error' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //var_dump($datas->file);

        $data = new stdClass;
        

        $path = storage_path("app/".$file->store("publics"));

        $data->file["path"] = $path;

        $data->file["name"] = $file->getClientOriginalName(); //$datas["title"];

        ///// analyse media ///

        $mediaDetails = $mediaAnalysing->path($path)->detailsToObject();

        //// end analyse ///
        
        if($mediaDetails->playtime_seconds >= 3600){
            unlink($path);
            return $this->errorResponse('undefined',['error' => "time of video most be down of 1 Hour (3600 seconds)"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //     $file = $request->file('image');

        //     //Display File Name
        //    $file->getClientOriginalName();


        //     //Display File Extension
        //    $file->getClientOriginalExtension();


            //Display File Real Path
        // $data->file = $file->getRealPath();


        //     //Display File Size
        //    $file->getSize();


        //     //Display File Mime Type
        //     $file->getMimeType();

        //     //Move Uploaded File
        // $destinationPath = 'uploads';

            //$file->move($destinationPath,$file->getClientOriginalName());
        //shortfilms, videogames, news
        $data->details = [
            "title" => $data->file["name"],
            "published" => true,
            "channel" => "comedy",
            "tags" => "",
            "is_created_for_kids" => false,
        ];

        //// save locale file link in the database before upload ////
        $media = [
            "details" => $data->details,
            "current_path" =>  $data->file["path"],
        ];

        /// check differents cases ///

        if (isset($anotherFileds['media_id'])) {

            $media['media_id'] = $anotherFileds['media_id'];

            $serverDatas = [
                'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                'id' => $media['media_id'],
                'table' =>'medias',
            ];

            $oldMedia = Medias::where('media_id', $media['media_id'])->first();
            
            if(!$anotherFileds['is_film_bande']){


                $link = 'api/getMediaData/'.$media["media_id"]/*.'?access='.Crypt::encrypt($request->bearerToken()) */;

                $serverDatas['link'] =  $link; // $medias[$column]

                $serverDatas['duration'] =  $mediaDetails->playtime_string??null;
            
            }else{

                //penser à mettre à jour la durée de la vidéo

                $media['is_film_bande'] = true;

                $link = 'api/getMediaBandeData/'.$media["media_id"];

                $serverDatas['bandeLink'] =  $link; // $medias[$column]

            }
            
            /*  $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => 'application/json',
                ])->post(env('OZ_STREAM_SERVER').'/api/server/add/media/link', $serverDatas);
                */

        }elseif (isset($anotherFileds['saison_id'])) {

            $media['saison_id'] = $anotherFileds['saison_id'];

            $link = 'api/getSaisonBandeData/'.$anotherFileds["saison_id"];

            $serverDatas = [
                'clientServerToken' => env('CLIENT_SERVER_TOKEN'),
                'id' => $media['saison_id'],
                'table' =>'saisons',
                'bandeLink' =>  $link,
            ];

            $oldMedia = Medias::where('saison_id', $media['saison_id'])->first();
            
        }

        if($oldMedia){

            // delete the old file //

            if(File::exists($oldMedia->current_path)){
                unlink( $oldMedia->current_path);
            }

            isset($media['media_id'])?($oldMedia->media_id = $media['media_id']):false;

            isset($media['saison_id'])?($oldMedia->saison_id = $media['saison_id']):false;

            $oldMedia->details = $media["details"];

            $oldMedia->current_path = $media["current_path"];

            $oldMedia->is_online = false;

            $media = $oldMedia->save();

            $media = $oldMedia;

        }else{

            $media = Medias::create($media) ;

        }
       
        //var_dump($media);

        $response = static::postServer('/api/server/add/media/link', $bearerToken, $serverDatas);

        if( $response->successful() ){

            ///unlink($media["current_path"]);

            

        //$mediaInfo->temporalyLink = $expiredLink;
            $data = (object)[];
        
            $data->link = env('PUBLIC_APP_URL').'/'.$link;
    
        /// lancer la job de covertion

        // run the convertion ///
            ConvertMediasJob::dispatch((object)$media);//->delay(now()->addSeconds(60));
           // UploadMediaOnDailyMotion::dispatch((object)$media);//->delay(now()->addSeconds(60));

            return  $this->successResponse($data, ['success' => 'this user can read'], Response::HTTP_CREATED);

        }elseif( $response->failed() ){

            unlink($path);

            return  $this->errorResponse($response->json(), ['error' => 'request error'], $response->status());

        }

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

        $media = Medias::where('media_id',$media_id)->orderBy('updated_at', "desc")->first();

        if (!$media) {

            return  $this->errorResponse('invalid media', null, Response::HTTP_NOT_FOUND);
            
        }

        return $this->getResources($media);

    }

    /**
     * @OA\Post(
     *      path="/api/getMediaData/{media_id}",
     *      operationId="streamming",
     *      tags={"media streamming"},
     *      summary="Uget datas streams",
     *      description="return the content",
     * @OA\Parameter(name="media_id",description="id of media",in="path", @OA\Schema(type="string" )),
     * @OA\Parameter(name="access",description="user token on base64",in="path", @OA\Schema(type="string" )),
     *     
     *      @OA\Response(
     *          response=200,
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

    public function getMediaFormat(Request $request, $media_id){

        $bearerToken = base64_decode($request->only('access')['access']??null); // Crypt::decrypt($request->only('access')['access'])??null; //$request->bearerToken();

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

        // $response = static::postServer('/api/server/user/bought', $bearerToken, $serverDatas );

        // if( /*true */  $response->successful()){

             // verifie si le finaLink n'est pas null
        
            $media = Medias::where('media_id',$media_id)->first();
        
            if (!$media) {
                return  $this->errorResponse('invalid media', null, Response::HTTP_NOT_FOUND);
                
            }

            $data = $media->converted_format["files"];

            return  $this->successResponse($data, 'format list', 200);

        // }elseif( $response->failed() ){

        //     return  $this->errorResponse($response->json(), ['error' => 'request error'], $response->status());

        // }

       

    }

        /**
     * @OA\Post(
     *      path="/api/getMediaFormat/{media_id}",
     *      operationId="streamming",
     *      tags={"media streamming"},
     *      summary="Uget datas streams",
     *      description="return the content",
     * @OA\Parameter(name="media_id",description="id of media",in="path", @OA\Schema(type="string" )),
     * @OA\Parameter(name="access",description="user token on base64",in="path", @OA\Schema(type="string" )),
     *     
     *      @OA\Response(
     *          response=200,
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

    public function getMediaData(Request $request, $media_id){

        $bearerToken = base64_decode($request->only('access')['access']??null); // Crypt::decrypt($request->only('access')['access'])??null; //$request->bearerToken();

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
        $response = static::postServer('/api/server/user/bought', $bearerToken, $serverDatas );

        if( /*true */  $response->successful()){

             // verifie si le finaLink n'est pas null
        
            $media = Medias::where('media_id',$media_id)->first();
        
            if (!$media) {
                return  $this->errorResponse('invalid media', null, Response::HTTP_NOT_FOUND);
                
            }

            $this->getResources($media);


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
        
        $media = Medias::where('saison_id',$saison_id)->first();

        if (!$media) {
            return  $this->errorResponse('invalid media', null, Response::HTTP_NOT_FOUND);
            
        }

      return $this->getResources($media);


    }

    public function inline($path, $name = null, $lifetime = 0)
    {

        if(File::exists($path)){

            $mineType = File::mimeType($path);

            //$isFile = true;

        }else{
            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
        }

        
       
       
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

        return response(File::get($path), 200, $headers);


    }
}
