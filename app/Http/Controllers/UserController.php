<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\Client as Mongo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Mail\VerifyMail;

// Users Controller Class
class UserController extends Controller
{   
    // User Registration Method
    public function register(Request $request){
        
        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;

        // Registration Validation
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Creating registration Token for verification
        $verificationToken = $this->createToken($fields['email']);
        $url = 'http://127.0.0.1:8000/api/emailVerification/' .$verificationToken. '/' .$request->email;

        // Creating User in DB
        $user = $collection->insertOne([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'url'=> $url,
            'VerificationToken' => $verificationToken,
            'EmailVerifiedAt' => null
        ]);

        // Sending Mail to verify
        Mail::to($fields['email'])->send(new VerifyMail($url,"retiyo3055@niekie.com"));
        return response([
            'status'=>'200',
            'message'=>'Email has been sent'
        ]);
    }

    // Email Verification Method
    public function emailVerification($token,$email){  

        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;
        
        // Finding against email
        $emailVerifyCollection = $collection->findOne(['email'=> $email]);
        
        // Checks
        if($emailVerifyCollection['EmailVerifiedAt'] != null){
            return response([
                'message'=>'Already Verified!'
            ]);
        }else if ($emailVerifyCollection) {

            // Updating against email
            $collection->updateOne(
                ['email' => $email],
                ['$set' => 
                    ['EmailVerifiedAt' => date('Y-m-d h:i:s')]
                ],
            );

            // Successful Response
            return response([
                'status'=>'200',
                'message'=>'Eamil Verified'
            ]);
        }else{

            // Error Response
            return response([
                'status'=>'400',
                'message'=>'Error'
            ]);
        }
    }

    // User LogIn Method
    public function login(Request $request){
        
        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;
       
        // LogIn Validation
        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Finding against email
        $logIn = $collection->findOne(
            [
                'email'=> $fields['email']
            ]
        );
        
        // Checks
         if ($logIn['EmailVerifiedAt'] == null) {

            // Error Response
            return response([
                'Status' => '400',
                'message' => 'Bad Request',
            ]);
        }else{

            // Checks
            if ($fields['email'] == $logIn['email'] and
                Hash::check($fields['password'], $logIn['password'])) {
                
                // Creating to MongoDB 
                $collection = (new Mongo())->SocialsiteMongo->users;

                // Finding against ID
                $user = $collection->findOne([
                    '_id' => $logIn['_id']
                ]);

                // Checks
                if (isset($user)) {

                    // Creating Token for user's Login
                    $LogInToken = $this->createToken($user->_id);

                    // Updating against ID
                    $token_save =  $collection->updateOne(
                        ['_id' => $user['_id']],
                        ['$set' => ['LogInToken' => $LogInToken]]
                    );

                    // Successful Response
                    return response([
                        'Status' => '200',
                        'Message' => 'Successfully Login',
                        'user_id' => $user->_id,
                        'Email' => $request->email,
                        'LogInToken' => $LogInToken
                    ]);
                }
            } else {

                // Error Response
                return response([
                    'Status' => '400',
                    'message' => 'Bad Request'
                ], 400);
            }
        }                     
    }

    // User LogOut Method
    public function logout(Request $request){

        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;
                
        // Getting Token
        $getToken = $request->bearerToken(); 

        // Decoding and Getting ID from Token in array
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce","HS256"));
        $userID = $decoded->id;

        // Decoding and Getting ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        // Finding against ID
        $user = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectID($strID)]);
       
        // Checks
        if ($collection->findOne(['LogInToken' == null])) {
            return response([
                "status" => "400",
                "message" => "This user is already logged out"
            ]);
        }

        // Deleting User's Token
        $userExist = $collection->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['LogInToken' => ['LogInToken' => null]]]
        );

        // Successful Response
        return response([
            "status" => "200",
            "message" => "logout successfully"
        ]);
    }

    // Create Token Method
    public function createToken($id){
        $key = "ProgrammersForce";
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000/api",
            "iat" => time(),
            "nbf" => 1357000000,
            "exp" => time() + 10000,
            "id" => $id
        );
        $token = JWT::encode($payload, $key, 'HS256');
        return $token;
    }

    // User's Profile Method
    public function SeeProfile(Request $request){

        // Creating to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;
        
        // Getting Token
        $getToken = $request->bearerToken();
        
        // Finding against Token
        $check = $collection->findOne(['LogInToken' => $getToken]);
        
        // Checks
        if(!isset($check->LogInToken)){
            return response([
                "status" => "400",
                "message" => "Invalid Token"
            ]);
        }
        else{

            // Decoding and Getting User's ID in array
            $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
            $userID = $decoded->id;
            
            // Decoding and Getting User's ID in string
            $encoded = json_encode($userID);
            $decoded = json_decode($encoded, true);
            $strID = $decoded['$oid'];

            // Check
            if($userID) {

                // Finding against ID
                $profile = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectID($strID)]);
                
                // Successful Response
                return response([
                    "status" => "200",
                    "Profile" => $profile
                ]);
            }
        }
    }

    // User's Update Profile Method
    public function updateProfile(Request $request){
  
        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->users;
      
        // Getting Token
        $getToken = $request->bearerToken();
        
        // Decoding and Getting ID in array
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;
        
        // Decoding and Getting ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        // Finding against ID
        $userUpdate = $collection->find(['_id' => new \MongoDB\BSON\ObjectID($strID)]);
        
        // Getting Runtime Data in array
        $data_to_update = [];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, ['name','email', 'password'])) {
                $data_to_update[$key] = $value;
            }
        }

        // Check
        if($data_to_update == null)
        {
            return response([
                "status" => "204",
                "message" => "No content has entered!"
            ]);
        }

        // Check
        if (isset($request->email) ) {
            return response([
                "status" => "400",                
                "message" => "Email cannot be changed!"
            ]);
        }

        // Check
        if ($request->password != null ) {
            $data_to_update['password'] = Hash::make($request->password);
        }

        // Check
        if (isset($userUpdate)) {

            // updatinng against User's ID
            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectID($strID)],
                ['$set' => $data_to_update]
            );
            
            // Successful Response 
          return response([
                'Status' => '200',
                'message' => 'you have successfully Updated User Profile',
            ], 200);
        }else {
            // Error Response
            return response([
                'Status' => '400',
                'message' => 'User not found',
            ]);
        }
    }
}

