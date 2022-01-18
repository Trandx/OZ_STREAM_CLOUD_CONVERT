<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\OpenDriver\OpenDriveController;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateLinkJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Streaming\Clouds\CloudInterface;

class CustomCloudController implements CloudInterface
{
    
    /**
     * Upload a entire directory to a cloud
     * @param  string $dir
     * @param  array $options
     */
    public function uploadDirectory(string $dir, array $options): void
    {
        $op = new OpenDriveController();

        $folderName = date('Y-m-d').'_'.time();

         $resp = (array)$op->CreateFolder($folderName);

         if($resp["success"]){

            //TODO faire une enrégistrement des dossier dans la bd

            ///

           // die ( var_dump($result) );

           foreach (File::allFiles($dir) as $file) {

                if (pathinfo($file, PATHINFO_EXTENSION) === 'ts') {

                    $fileName = $file->getBasename(); 

                    $chunckFileName = explode('_', $fileName);

                    $path = $dir.$fileName;

                    $result = (array)$op->UploadFile($path,$resp['datas']['FolderID']);

                    if($result["success"]){

                        $m3u8File = $dir.$chunckFileName[0].'_'.$chunckFileName[1].'.m3u8';

                        $content = file_get_contents($m3u8File);

                        $content = str_replace( $fileName, $result['datas']['StreamingLink'], $content);
                        
                        file_put_contents($m3u8File, $content);

                        unlink($path);
                    }
                    
                }

            }
            

           /* foreach (File::allFiles($dir) as $file) {

                if (pathinfo($file, PATHINFO_EXTENSION) === 'm3u8') {

                    $fileName = $file->getBasename(); 

                    if($file->getBasename() != $chunckFileName[0].'.m3u8'){

                        $chunckFileName = explode('_', $fileName);

                        $path = $dir.$fileName;

                        $result = (array)$op->UploadFile($path,$result['datas']['FolderID']);

                        if($result["success"]){

                            $m3u8File = $dir.$chunckFileName[0].'.m3u8';

                            $content = file_get_contents($m3u8File);

                            $content = str_replace( $fileName, $result['datas']['StreamingLink'], $content);
                            
                            file_put_contents($m3u8File, $content);

                            unlink($path);
                        }
                    }

                }

            }*/
        
        }

        // déplacer les m3u8
            // File::files($dir)
            //var_dump($options);

            $folder = $options['finalFolder'];

            foreach (File::files($dir) as  $file) {
               
                File::copy( $file->getRealPath(),$folder.'/'.$file->getBasename()) ;

                if($file->getBasename() == $chunckFileName[0].'.m3u8'){
                    $finalPath = $folder.'/'.$file->getBasename();
                }
                

            }

            // appel d'une job pour la save
           // var_dump($options);
           UpdateLinkJob::dispatch( $finalPath, $options);

    }

    /**
     * Download a file from a cloud
     * @param  string $save_to
     * @param  array $options
     */
    public function download(string $save_to, array $options): void
    {
        // TODO: Implement download() method.
    }
}
