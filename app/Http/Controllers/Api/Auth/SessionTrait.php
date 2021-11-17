<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ResponseController as resp;
use App\Models\TokenDiffuserSpace;
use Illuminate\Http\Response;
use Illuminate\Http\Request as req;
trait SessionTrait{


   public static function userApi(){

       return auth()->guard('api');
   }

   public static function getUserApi(){

        return self::userApi()->user();

   }

   public static function getUserIdApi(){
    return self::getUserApi()->id;
   }

   public static function isConnected(){

        if(self::getUserApi()){

            return true;

        }

        return false;

   }

}
