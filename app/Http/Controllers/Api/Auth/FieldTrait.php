<?php

namespace App\Http\Controllers\Api\Auth;


trait FieldTrait
{

    public function UserFieldTrait( $fields, $fieldAutho=[])
    {


        $privateField = [
            'accountStatus',
            'role_id' ,
            'update_role_at ' ,
            'role_given_by' ,
            'password' ,
            'cardNumber',
        ];

        foreach ($fields as $key => $value) {

           if(in_array($key, $privateField) && !in_array($key,$fieldAutho) ){
               return [true,$key];
           }

        }

    }
}
