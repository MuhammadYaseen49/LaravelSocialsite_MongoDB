<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post("/register", [MainController::class, "register"]);
Route::get('emailVerification/{token}/{email}',[MainController::class,'emailVerification']);
Route::post("login", [MainController::class, "login"]);

Route::group(["middleware" => ["verification"]], function(){

    // Route::get("profile", [StudentController::class, "profile"]);
    Route::post("logout", [MainController::class, "logout"]);
    Route::post("seeProfile", [MainController::class, "seeProfile"]);
    Route::post("updateProfile/{id}", [MainController::class, "updateProfile"]);

    // POST Routes
    Route::post("createPost", [PostController::class, "createPost"]);
    Route::get("listPost", [PostController::class, "listPost"]);
    Route::get("myPost", [PostController::class, "myPost"]);
    Route::post("updatePost/{id}", [PostController::class, "updatePost"]);
    Route::delete("deletePost/{id}", [PostController::class, "deletePost"]);

  
});
