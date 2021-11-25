<?php

namespace App\Http\Controllers\NoApi;

use App\Http\Controllers\Api\Auth\SessionTrait;
use App\Http\Controllers\Api\ResponseController;
//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends ResponseController
{
    use SessionTrait;
    protected $videoExt = [
        'mp4','mkv' //|max:2048
    ];
    protected $imageExt = [
        'jpg','jpeg','png','ico',//|max:2048

    ];

    protected $imgMinSize = [
        'height' => 1,
         'width' => 0,
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fileVerif($request, $fileType=['image','jpeg'], $file=null) //$fileType=['image','jpeg']
    {

        //echo in_array($fileType[1],$this->{$fileType[0].'Ext'});
        if(! in_array($fileType[1],$this->{$fileType[0].'Ext'})){
            return [false, 'unaccepted file type'];
        }

        if($fileType[0] == 'image'){

            $image = getimagesize($file);
            $width = $image[0];
            $height = $image[1];

            if($width >= $this->imgMinSize['width'] && $height >= $this->imgMinSize['height']){
                return [true];
            }else{
                return [false, 'this image don\'t acceptable size'];
            }
        }

         return [true];

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($request,  $path)
    {

        $file = $request->file();
        $fileInfo = null;


            foreach ($file as $value) {

                    //$type = explode("/",$value->getMimeType()); //video/mp4

                  $fileName = $request->id.time().'.'.$value->getClientOriginalExtension();
                  $content = $value->getContent();
                  $path = $path.'/'.$fileName;
                    //verification de extensions
                   // $verifData = $this->fileVerif($request,$type, $value);


                        // $filePath = $file->store($link)

                        // store file
                        Storage::disk('public_user')->put($path, $content);
                        //$filePath = $request->file($key)->storeAs("$path", $fileName, 'public');

                    /* $fileModel->name = $fileName;
                        $fileModel->file_path = '/storage/' . $filePath;
                        $fileModel->save();*/

                $fileInfo[] = ["name" => $fileName, "file_path" => $path];

            }

            return $fileInfo;

    }

        /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function mediaStore($request,  $path)
    {

        $file = $request->file();
        $fileInfo = null;


            foreach ($file as $value) {

                    //$type = explode("/",$value->getMimeType()); //video/mp4

                  $fileName = $request->id.time().'.'.$value->getClientOriginalExtension();
                  $content = $value->getContent();
                  $path = $path.'/'.$fileName;
                    //verification de extensions
                   // $verifData = $this->fileVerif($request,$type, $value);


                        // $filePath = $file->store($link)

                        // store file
                        Storage::disk('local_user')->put($path, $content);
                        //$filePath = $request->file($key)->storeAs("$path", $fileName, 'public');

                    /* $fileModel->name = $fileName;
                        $fileModel->file_path = '/storage/' . $filePath;
                        $fileModel->save();*/

                $fileInfo[] = ["name" => $fileName, "file_path" => $path];

            }

            return $fileInfo;

    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function joinFile($request,  $path)
    {
        $file = $request->file();
        $fileInfo = null;

            foreach ($file as $key=>$value) {

                  $fileName = self::getUserIdApi().time().'.'.$value->getClientOriginalExtension();
                  $content = $value->getContent();
                  $path = $path.'/'.$fileName;

                    Storage::disk('public_user')->put($path, $content);

                $fileInfo[$key] =  $path;

            }

            return $fileInfo;
    }


}
