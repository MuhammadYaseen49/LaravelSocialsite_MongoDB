<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\Post;
use App\Models\Comment;
use App\Models\FriendRequest;

use Illuminate\Support\Facades\DB;


class CommentController extends Controller
{
    public function createComment(Request $request)
    {
        // dd("asas");
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('ProgrammersForce', 'HS256'));
        $request->validate([
            'post_id' => 'integer',
            'comments' => 'string',
        ]);

        $post_exists = Post::where('id', '=', $request->post_id)->first();
        // dd($post_exists);

        if (isset($post_exists)) {
            if ($post_exists->privacy == 'Public' or $post_exists->privacy == 'public') {
                $attachment = null;
                if ($request->file('attachment') != null) {
                    $attachment = $request->file('attachment')->store('commentFiles');
                }

                $comment = Comment::create([
                    'user_id' => $decode->data,
                    'post_id' => $request->post_id,
                    'comments' => $request->comments,
                    'attachment' => $attachment
                ]);

                if (isset($comment)) {
                    return response([
                        'message' => 'Comment Created Succesfully',
                        'Comment' => $comment,
                    ]);
                } else {
                    return response([
                        'message' => 'Something Went Wrong While added Comment',
                    ]);
                }
            } elseif ($post_exists->privacy == 'Private' or $post_exists->privacy == 'private') {
                $userSeen = DB::select('select * from friends where ((sender_id = ? AND reciver_id = ?) OR (sender_id = ? AND reciver_id = ?)) AND status = ?', [$post_exists->user_id, $decode->data, $decode->data, $post_exists->user_id, 'Accept']);
                if (!empty($userSeen)) {
                    $attachment = null;
                    if ($request->file('attachment') != null) {
                        $attachment = $request->file('attachment')->store('commentFiles');
                    }

                    $comment = Comment::create([
                        'user_id' => $decode->data,
                        'post_id' => $request->post_id,
                        'comments' => $request->comments,
                        'attachment' => $attachment
                    ]);

                    if (isset($comment)) {
                        return response([
                            'message' => 'Comment Created Succesfully',
                            'Comment' => $comment,
                        ]);
                    } else {
                        return response([
                            'message' => 'Something Went Wrong While added Comment',
                        ]);
                    }
                } else {
                    return response([
                        'message' => 'This is Private Post. You are not authorize to Comment on this Post',
                    ]);
                }
            }
        } else {
            return response([
                'message' => 'No Post Found',
            ]);
        }
    }


    public function updateComment(Request $request, $id)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('ProgrammersForce', 'HS256'));

        $comment_exists = Comment::where('id', '=', $id)->first();
        if (isset($comment_exists)) {
            $post_privacy = POST::where('id', '=', $comment_exists->post_id)->first();
            if ($post_privacy->privacy == 'Public' or $post_privacy->privacy == 'public') {
                if ($request->file('attachment') != null and $comment_exists->attachment != null) {
                    unlink(storage_path('app/' . $comment_exists->attachment));
                }
                $comment_exists->update($request->all());

                if ($request->file('attachment') != null) {
                    $comment_exists->attachment = $request->file('attachment')->store('commentFiles');
                    $comment_exists->save();
                }
                return response([
                    'Updated Comment' => $comment_exists,
                    'message' => 'Comment Updated Succesfully',
                ]);
            } elseif ($post_privacy->privacy == 'Private' or $post_privacy->privacy == 'private') {
                $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post_privacy->user_id, $decode->data, $decode->data, $post_privacy->user_id, 'Accept']);
                if (!empty($userSeen)) {
                    if ($request->file('attachment') != null) {
                        unlink(storage_path('app/' . $comment_exists->attachment));
                    }
                    $comment_exists->update($request->all());
                    if ($request->file('attachment') != null) {
                        $comment_exists->attachment = $request->file('attachment')->store('commentFiles');
                        $comment_exists->save();
                    }
                    return response([
                        'Updated Comment' => $comment_exists,
                        'message' => 'Comment Updated Succesfully',
                    ]);
                } else {
                    return response([
                        'message' => 'This Post is Private and you are not a friend.',
                    ]);
                }
            }
        } else {
            return response([
                'message' => 'No Post Found',
            ]);
        }
    }

    public function deleteComment(Request $request, $id)
    {
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('ProgrammersForce', 'HS256'));

        $comment = Comment::where('id', '=', $id, 'AND', 'user_id', '=', $decode->data)->first();
        if (isset($comment)) {
            if ($comment->attachment != null) {
                unlink(storage_path('app/' . $comment->attachment));
            }
            $comment->delete();
            return response([
                'message' => 'Comment has been Deleted',
            ]);
        } else {
            return response([
                'message' => 'You are Unauthorize to Delete Comment',
            ]);
        }
    }
}
