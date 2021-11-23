<?php

namespace App\Http\Controllers;
use App\Models\Post;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\Client as Mongo;
use Illuminate\Http\Request;
class PostController extends Controller{
    public function createPost(Request $request){ 
        $request->validate([
            "title"=>"required",
            "body"=>"required",
            "privacy"=>"required"
        ]);
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;

        $collection = (new Mongo())->SocialsiteMongo->posts;

        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $str_decode = $decoded['$oid'];

        $collection->insertOne([
            'user_id' => $str_decode,
            'title' => $request->title,
            'body' => $request->body,
            'attachment' => $request->file('attachment')->store('Attachments_Folder'),
            'privacy' => $request->privacy
        ]);

        return response([
            'Status' => '200',
            'message' => 'successfully Posted',
        ], 200);

    }

    public function listPost(){
        $collection = (new Mongo())->SocialsiteMongo->posts;
        $allposts = $collection->find();
        if (is_null($allposts)) {
            return response()->json('Data not found', 404);
        }
        return response($allposts->toArray());
    }

    public function myPost(Request $request){
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;
       
        $encoded = json_encode($userID);
        $decoded = json_decode($encoded, true);
        $str_decode = $decoded['$oid'];

        $collection = (new Mongo())->SocialsiteMongo->posts;
        $myPosts = $collection->find(['user_id' => $str_decode]);

        if($myPosts){
            return response()->json([
                "status"=>"1",
                "message"=>"Post found!",
                "data"=>$myPosts
            ]);
        }else{
            return response()->json([
                "status"=>"0",
                "message"=>"Post not found!",
            ], 404);
        }
    }

    public function updatePost(Request $request, $id){
         $getToken = $request->bearerToken();
         if (!isset($getToken)) {
             return response([
                 'message' => 'Bearer token not found'
             ]);
         }

         $decoded = JWT::decode($getToken, new Key('ProgrammersForce', 'HS256'));
         $userId = $decoded->id;
         $collection = (new Mongo())->SocialsiteMongo->posts;
         $data_to_update = [];
         foreach ($request->all() as $key => $value) {
             if (in_array($key, ['title', 'body'])) {
                 $data_to_update[$key] = $value;
             }
         }
         $postCollection = $collection->findOne([
             '_id' => new \MongoDB\BSON\ObjectId($id),
             'user_id' => $userId
         ]);

         if ($postCollection == null) {
             return response([
                 'message' => 'No such post exists'
             ]);
         }

         if (!empty($postCollection)) {
             $collection->updateOne(
                 [
                     '_id' => new \MongoDB\BSON\ObjectId($id),
                     'user_id' => $userId
                 ],
                 ['$set' => $data_to_update]
             );

             if ($request->file('attachment') != null) {
                 $file = $request->file('attachment')->store('postFiles');

                 $collection->updateOne(
                     [
                         '_id' => new \MongoDB\BSON\ObjectId($id),
                         'user_id' => $userId
                     ],
                     ['$set' => ['attachment' => 'http://127.0.0.1:8000/storage/app/' . $file]]
                 );
             }

             if ($request->privacy != null) {
                 $collection->updateOne(
                     [
                         '_id' => new \MongoDB\BSON\ObjectId($id),
                         'user_id' => $userId
                     ],
                     ['$set' => ['privacy' => $request->privacy]]
                 );
             }
             return response([
                 'message' => 'Updated Successfully'
             ]);
         }
    }

    public function deletePost(Request $request, $id){
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->id;

            $collection = (new Mongo())->SocialsiteMongo->posts;
            $encoded = json_encode($userID);
            $decoded = json_decode($encoded, true);
            $str_decode = $decoded['$oid'];

        $delete_post = $collection->findOne(['user_id' => $str_decode]);
        $not_exists = $collection->find(['_id' =>  new \MongoDB\BSON\ObjectID($id)]);
        if ($not_exists->toArray() == null) {
           return response([
               'message' => 'Post Not Exits',
           ]);
        }

        if (isset($delete_post)) {
           $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);

           return response([
               'Status' => '200',
               'message' => 'you have successfully Deleted Entry',
               'Deleted Post ID' => $id
          ], 200);
        }else {
           return response([
               'Status' => '201',
               'message' => 'you are not Authorize to delete other User Posts'
            ], 200);
       }
    }
}
