<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\OpenDriver\OpenDriveController;
use App\Models\OpenDriverFolder;
//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OpenDriverController extends OpenDriveController
{


    public function opd_login(Request $request){
        $data = $request->all();

        if(isset($data['username'], $data['password']))
            return $this->Login($data['username'], $data['password'] );

        return $this->Login();
    }

    public function opd_uploadFile(Request $request){
        $data = $request->all();

        if(isset($data['folder_id'] /*, $data['file_name'], $data['file_id']*/))
            return $this->UploadFile($request, $data['folder_id'] /*, $data['file_name'], $data['file_id']*/); 

        return $this->UploadFile($request);
    }

    public function opd_createFolder(Request $request){

        $field = [
            'parent_folder_id' => 'required|string',
            'folder_name' => 'required|string|min:2',
            'description' => 'required|string|min:2',
        ];

        $data = $request->all();

        $validator = Validator::make($data,$field);

        if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], 400);
        }

        /// lorsque la creation est ok, un enregistrement doit se faire dans la bd
        
        $backData = $this->CreateFolder($data['parent_folder_id'],$data['folder_name'], $data['description']);

        $data =  [ 
            'Name' => $backData["Name"],
            "FolderID" => $backData["FolderID"],
            "Description" => $backData["Description"],
            "Link" => $backData["Link"],
        ];

        $backData = OpenDriverFolder::create($data);
    }

    public function opd_getFolderAndFileList (Request $request){

        $field = [
            'folder_id' => 'required|string',
        ];
        $data = $request->all();

        $validator = Validator::make($data,$field);

        if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], 400);
        }
        
        // lorsqu'une selection est ok, nous devons mettre à jour la bd

        return $this->GetFolderList($data['folder_id']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $id)
    {
        
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
