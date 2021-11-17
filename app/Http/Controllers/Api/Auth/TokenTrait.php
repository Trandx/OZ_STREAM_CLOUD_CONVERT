<?php


//namespace App\Http\Controllers\Api\Auth;
namespace App\Http\Controllers\Api\Auth;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

trait TokenTrait
{

    public function issueToken(Request $request, $grantType, $scope = "*")
    {
 //var_dump($this->client);
 //var_dump($request->accessToken);

        $params = [
            'grant_type' => $grantType,
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'username' => $request->email ?? $request->phone,
            //'password' => request('password'),
            'scope' => $scope,
        ];

        $request->request->add($params);

        $proxy = Request::create('oauth/token', 'POST');

        return Route::dispatch($proxy);
    }
}
