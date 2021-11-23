<?php

namespace App\Http\Controllers;

use App\Mail\VerifyMail;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use MongoDB\Client as Mongo;


class MainController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){}

    //Register Action
    public function register(Request $request){
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
        ]);

        $verificationToken = $this->createToken($fields['email']);
        $url = 'http://127.0.0.1:8000/api/emailVerification/' .$verificationToken. '/' .$request->email;

        $collection = (new Mongo())->SocialsiteMongo->users;
        $user = $collection->insertOne([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'url'=> $url,
            'VerificationToken' => $verificationToken,
            'EmailVerifiedAt' => null
        ]);

        Mail::to($fields['email'])->send(new VerifyMail($url,"retiyo3055@niekie.com"));
        return response([
            'message'=>'Email has been sent'
        ]);
    }

    public function emailVerification($token,$email){  
        $collection = (new Mongo())->SocialsiteMongo->users;
        $emailVerifyCollection = $collection->findOne(['email'=> $email]);

        if($emailVerifyCollection['EmailVerifiedAt'] != null){
            return response([
                'message'=>'Already Verified!'
            ]);
        }else if ($emailVerifyCollection) {
            $collection->updateOne(
                ['email' => $email],
                ['$set' => 
                    ['EmailVerifiedAt' => date('Y-m-d h:i:s')]
                ],
            );
            return response([
                'message'=>'Eamil Verified'
            ]);
        }else{
            return response([
                'message'=>'Error'
            ]);
        }
    }

    public function login(Request $request){
        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $collection = (new Mongo())->SocialsiteMongo->users;
        $user = $collection->findOne(
            [
                'email'=> $fields['email']
            ]
        );
         if ($user['EmailVerifiedAt'] == null) {
            return response([
                'Status' => '400',
                'message' => 'Bad Request',
                'Error' => 'Please Verify your Email before login'
            ], 400);
        }else{
            if ($fields['email'] == $user['email'] and
                Hash::check($fields['password'], $user['password'])) {
                $collection = (new Mongo())->SocialsiteMongo->users;
                $user = $collection->findOne([
                    '_id' => $user['_id']
                ]);
                if (isset($user)) {
                    $LogInToken = $this->createToken($user->_id);
                    $token_save =  $collection->updateOne(
                        ['_id' => $user['_id']],
                        ['$set' => ['LogInToken' => $LogInToken]]
                    );
                    return response([
                        'Status' => '200',
                        'Message' => 'Successfully Login',
                        'user_id' => $user->_id,
                        'Email' => $request->email,
                        'LogInToken' => $LogInToken
                    ], 200);
                }
            } else {
                return response([
                    'Status' => '400',
                    'message' => 'Bad Request',
                    'Error' => 'Email or Password doesnot match'
                ], 400);
            }
        }                     
    }

    public function logout(Request $request){
        $getToken = $request->bearerToken(); 
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce","HS256"));
        $userID = $decoded->id;
        $collection = (new Mongo())->SocialsiteMongo->users;
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $str_decode = $decoded['$oid'];

        $user = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectID($str_decode)]);
       
       if ($collection->findOne(['LogInToken' == null])) {
            return response([
                "message" => "This user is already logged out"
            ], 404);
        }

        $userExist = $collection->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['LogInToken' => ['LogInToken' => null]]]
        );

        return response([
            "message" => "logout successfully"
        ], 200);
    }

    public function createToken($id){
        $key = "ProgrammersForce";
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000/api",
            "iat" => time(),
            "nbf" => 1357000000,
            "exp" => time() + 1000,
            "id" => $id
        );
        $token = JWT::encode($payload, $key, 'HS256');
        return $token;
    }

    public function SeeProfile(Request $request){
        //get token from header
        $getToken = $request->bearerToken();
        $collection = (new Mongo())->SocialsiteMongo->users;
        
        // if token is invalid
        $check = $collection->findOne(['LogInToken' => $getToken]);
        if(!isset($check->LogInToken)){
            return response([
            "message" => "Invalid Token"
            ], 200);
        }
        else{
            $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
            $userID = $decoded->id;
            $encoded = json_encode($userID);
            $decoded = json_decode($encoded, true);
            $str_decode = $decoded['$oid'];
            if($userID) {

                $profile = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectID($str_decode)]);
                return response([
                    "Profile" => $profile
                ], 200);
            }
        }
    }

    public function updateProfile(Request $request, $id){
        $getToken = $request->bearerToken();
        $collection = (new Mongo())->MongoApp->users;
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $str_decode = $decoded['$oid'];
        $userUpdate = $collection->find(['_id' => new \MongoDB\BSON\ObjectID($id)]);
        if (isset($userUpdate)) {
            $userExist = $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectID($str_decode)],
                ['$set' => ['name' => $request->name, 'password' => Hash::make($request->password)]]
            );
            return response([
                'Status' => '200',
                'message' => 'you have successfully Update User Profile',
            ], 200);
        }else {
            return response([
                'Status' => '200',
                'message' => 'User not found',
            ], 404);
        }
    }
}

