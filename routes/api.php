<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register',[App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login',[App\Http\Controllers\AuthController::class, 'login']);
Route::post('/getUserData',[App\Http\Controllers\APIController::class, 'getUserData']);
Route::post('/logout',[App\Http\Controllers\AuthController::class, 'logout']);

Route::post('/getUsers',[App\Http\Controllers\APIController::class, 'getUsers']);
Route::post('/getRoles',[App\Http\Controllers\APIController::class, 'getRoles']);
Route::post('/getUserStatuses',[App\Http\Controllers\APIController::class, 'getUserStatuses']);
Route::post('/updateUser',[App\Http\Controllers\APIController::class, 'UpdateUser']);
Route::get('/selectedUserData/{userId}',[App\Http\Controllers\APIController::class, 'getSelectedUserData']);