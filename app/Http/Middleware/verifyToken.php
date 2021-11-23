<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use MongoDB\Client as Mongo;

class verifyToken
{
    public function handle(Request $request, Closure $next)
    {
        $getToken = $request->bearerToken(); 
        if(empty($getToken)){
            return response([
            "message" => "Token is Empty Please Enter Berear Token!"
            ], 200);
        }
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce","HS256"));
        $userID = $decoded->id;
        $collection = (new Mongo())->SocialsiteMongo->users; 
        $check = $collection->findOne(['LogInToken' => $getToken]);
        if(!isset($check)){
            return response([
                "message" => "Token Does not Exist"
            ], 200);
        }
        else{
            return $next($request);
        }
    }
}
