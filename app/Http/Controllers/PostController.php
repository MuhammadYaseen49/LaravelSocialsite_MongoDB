<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\Client as Mongo;
use Illuminate\Http\Request;

// User's Posts Controller Class
class PostController extends Controller
{
    // Creating Post Method
    public function createPost(Request $request){

        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->posts;
        
        // validation
        $request->validate([
            "title" => "required",
            "body" => "required",
            "privacy" => "required"
        ]);

        // getting Token
        $getToken = $request->bearerToken();

        // Decoding and getting ID in array
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;

        // Decoding and getting ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        // Check
        $attachment = null;
        if ($request->file('attachment') != null) {
            $attachment = $request->file('attachment')->store('postFiles');
        }

        // Creating Post
        $collection->insertOne([
            'user_id' => $strID,
            'title' => $request->title,
            'body' => $request->body,
            'privacy' => $request->privacy,
            'attachment' => $attachment
        ]);

        // Successful Response 
        return response([
            'Status' => '200',
            'message' => 'successfully Posted',
        ]);
    }

    // Displaying All Posts Method
    public function allPosts(){
        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->posts;

        // Finding all Posts
        $all_posts = $collection->find();

        // Check
        if (is_null($all_posts)) {
            // Error Response
            return response()->json('Data not found', 404);
        }

        // Successful Response
        return response($all_posts->toArray());
    }

    // Displaying Only User's Posts Method
    public function myPost(Request $request){

        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->posts;

        // Getting Token
        $getToken = $request->bearerToken();

        // Decoding and Getting User's ID in array
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;

        // Decoding and Getting User's ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        // Finding against ID
        $Posts = $collection->find([
            'user_id' => $strID
        ]);

        // Saving in array
        $myPosts = $Posts->toArray();

        // Checks
        if ($myPosts) {

            // Successful Response
            return response()->json([
                "status" => "1",
                "message" => "Post found!",
                "data" => $myPosts
            ]);
        } else {

            // Error Response
            return response()->json([
                "status" => "0",
                "message" => "Post not found!",
            ], 404);
        }
    }

    // Updating User's Post Method
    public function updatePost(Request $request, $id){

        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->posts;

        // Getting Token
        $getToken = $request->bearerToken();

        // Decoding and getting ID in array
        $decoded = JWT::decode($getToken, new Key('ProgrammersForce', 'HS256'));
        $userID = $decoded->id;

        // Decoding and getting ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        // Finding against ID
        $postCollection = $collection->findOne([
            '_id' => new \MongoDB\BSON\ObjectId($id)
        ]);

        // Checks
        if ($postCollection == null) {
            return response([
                'message' => 'No such post exists'
            ]);
        }

        // Saving Runtime Data in Array
        $data_to_update = [];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, ['title', 'body', 'privacy', 'attachment'])) {
                $data_to_update[$key] = $value;
            }
        }

        //  Checks
        if (isset($postCollection)) {

            // Check
            if ($request->file('attachment') != null) {

                $attachment = null;
                $file = $request->file('attachment')->store('postFiles');
                $store = 'http://127.0.0.1:8000/storage/app/' . $file;
                $data_to_update['attachment'] =  $store;
            }
           
            // Check
            if (isset($request->privacy)) {
                if ($request->privacy != null) {
                    if (($request->privacy == "public" || $request->privacy == "Public" ||
                        $request->privacy == "private" || $request->privacy == "Private")) {
                        
                        $data_to_update['privacy'] =  $request->privacy;
                        
                    } else {
                        return response([
                            'message' => 'Enter Public or Private'
                        ]);
                    }
                }
            }

            // Updating against ID
            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $data_to_update]
            );

            //  Successful Response
            return response([
                'message' => 'Updated Successfully'
            ]);
        }
    }

    // Deleting User's Post Method
    public function deletePost(Request $request, $id){
        
        // Connecting to MongoDB
        $collection = (new Mongo())->SocialsiteMongo->posts;

        // Getting Token
        $getToken = $request->bearerToken();

        // Decoding and getting ID in array
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;

        // Decoding and getting ID in string
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $strID = $decoded['$oid'];

        $findPost  = $collection->findOne(['user_id' => $strID]);
        $postExist = $collection->find(['_id' =>  new \MongoDB\BSON\ObjectID($id)]);

        // Check
        if ($postExist->toArray() == null) {
            return response([
                'status' => '404',
                'message' => 'Post Not Exit',
            ]);
        }

        // Check
        if (isset($findPost)) {
            $collection->deleteOne([
                '_id' =>  new \MongoDB\BSON\ObjectID($id),
                'user_id' => $strID
            ]);

            // Successful Response 
            return response([
                'Status' => '200',
                'message' => 'you have successfully Deleted Entry',
                'Deleted Post ID' => $id
            ], 200);
        } else {

            // Error Response
            return response([
                'Status' => '400',
                'message' => 'you are not Authorize to delete other User Posts'
            ], 200);
        }
    }
}
