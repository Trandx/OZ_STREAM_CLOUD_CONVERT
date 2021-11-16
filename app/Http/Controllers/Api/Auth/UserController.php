<?php

//namespace App\Http\Controllers\Api\Auth;
//use App\Http\Controllers\Api\ResponseController;
namespace App\Http\Controllers\Api\Auth;


use App\Http\Controllers\Api\ResponseController;
use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends ResponseController
{
        use FieldTrait;


    /**
     * @OA\Post(
     *      path="/api/register",
     *      operationId="registerUser",
     *      tags={"user"},
     *      summary="register new user",
     *      description="add new user and return the user data",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(required=true,@OA\JsonContent(),
     *                  @OA\MediaType(
     *               mediaType="multipart/form-data",
     *               @OA\Schema(
     *                  type="object",
     *                  required={"phone","password", "lastName"},
     *                  @OA\Property(property="phone", type="string"),
     *                  @OA\Property(property="password", type="text"),
     *                  @OA\Property(property="password_confirmation", type="text"),
     *                  @OA\Property(property="lastName", type="text")
     *               ),
     *           ),
     *
     * ),
     *      @OA\Response( response=201,description="user created successfully ", @OA\JsonContent(ref="")),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthenticated",),
     *
     * )
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerUser(Request $request)
    {

        $fields = [
            //'phone' => 'required|integer|unique:user',
            'accessToken' => 'required|string|min:4',
            'user_id' => 'required|string',
        ];

        $datas = $request->only('accessToken', 'user_id');
        $validator = Validator::make($datas,$fields);

        if ($validator->fails()) {
            return $this->errorResponse('Validation', ['error' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if($user = User::find($datas['user_id'])){
            $user->accessToken = $datas['accessToken'];
            $user->save();

            return $this->successResponse($user, ['success' => 'Cloud token is created'], Response::HTTP_CREATED);
        }

        $user = User::create($datas);

        return $this->successResponse($user, ['success' => 'Cloud token is created'], Response::HTTP_CREATED);

    }
    

}
