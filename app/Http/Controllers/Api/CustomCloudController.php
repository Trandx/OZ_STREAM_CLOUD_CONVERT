<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\OpenDriver\OpenDriveController;

use App\Jobs\UpdateLinkJob;

class CustomCloudController
{
    
    /**
     * Upload a entire directory to a cloud
     * @param  array $files_path
     * @param  array $options
     */
    public function uploadDirectory(array $files_path, array $options): void
    {
        $op = new OpenDriveController();

        $folderName = date('Y-m-d').'_'.time();

         $resp = (array)$op->CreateFolder($folderName);

         if($resp["success"]){

            //TODO faire une enrégistrement des dossier dans la bd

            ///

           // die ( var_dump($result) );

           foreach ($files_path as $key => $file) {

                $result = (array)$op->UploadFile($file["url"],$resp['datas']['FolderID']);

                if($result["success"]){

                    unlink($file["url"]);

                    $files_path[$key]["url"] = $result['datas']['StreamingLink'];
                }

                // if (pathinfo($file, PATHINFO_EXTENSION) === 'ts') {

                //     $fileName = $file->getBasename(); 

                //     $chunckFileName = explode('_', $fileName);

                //     $path = $dir.$fileName;

                //     $result = (array)$op->UploadFile($path,$resp['datas']['FolderID']);

                //     if($result["success"]){

                //         $m3u8File = $dir.$chunckFileName[0].'_'.$chunckFileName[1].'.m3u8';

                //         $content = file_get_contents($m3u8File);

                //         $content = str_replace( $fileName, $result['datas']['StreamingLink'], $content);
                        
                //         file_put_contents($m3u8File, $content);

                //         unlink($path);
                //     }
                    
                // }

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

            // appel d'une job pour la save
           // var_dump($options);
           UpdateLinkJob::dispatch( json_encode($files_path), $options);

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
