<?php

namespace App\Http\Controllers;

use App\Models\Friends;
use Illuminate\Http\Request;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class FriendsController extends Controller
{
    //
    public function sendFriendRequest(Request $request){

        $request->validate(
            [
                'reciver_id'=> 'required'
            ]
        );

        //get token from header and check user id
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->data;
       
        if ($userID == $request->reciver_id) {
            return response([
                "Message" => "You cannot Send Friend Request to yourself"
            ]);
        }
         //check if recever_user is exists in Users_table DB
         $users_table = User::where('id','=', $request->reciver_id)->first();
         $data = new Friends();

        //chcek if user is already sended request or not
        $check_alreadySent = Friends::where('sender_id',$userID)->where('reciver_id' , $request->reciver_id)->first();

        if (isset($check_alreadySent)) {
            return response([
                "Message" => "You have already Sent the Friend Request to this User"
            ]);
        }

        if(isset($users_table)) {
            $data->reciver_id = $request->reciver_id;
            $data->sender_id = $userID;
            $data->save();

            return response([
                "Message" => "You have Successfully Send Friend Request "
            ]);   
        }else {
            return response([
                "Message" => "This User Doesnot Exists in Records"
            ]);
        }
    }

    public function myRequests(Request $request){
        //get token from header and check user id
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->data;
        
        $req = Friends::all()->where('reciver_id' ,  $userID)->where('status', '0');

        // if(!isset($req)){

        //     return response()->json('You Dont have any Friend Request', 404); 
        // }

        if (isset($req)) {
            
            return $req;

        }
        
    }

    public function acceptRequest(Request $request){

        $request->validate(
            [
                'sender_id'=> 'required'
            ]
        );

        //get token from header and check user id
        $getToken = $request->bearerToken();
        $decoded = JWT::decode($getToken, new Key("ProgrammersForce", "HS256"));
        $userID = $decoded->data;
       
        if ($userID == $request->sender_id) {
            return response([
                "Message" => "You cannot Receive Friend Request of yourself"
            ]);
        }

        //check if recever_user is exists in Request Table DB
        $recive_req = Friends::where('sender_id',$request->sender_id )->where('reciver_id' , $userID)->first();

        if ($recive_req->status == '1') {

            return response([

                "Message" => "You are already Friend of this User"

            ]);

        }

        if(isset($recive_req)) {
            $recive_req->status = '1';
            $recive_req->save();
            return response([
                "Message" => "Congratulations! You are Friends Now"
            ]);   
        }else{
            return response([
                "Message" => "This User Doesnot Sent you Friend Request"
            ]);
        }
       
    }
}
