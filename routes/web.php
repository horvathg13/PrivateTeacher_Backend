<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery\Exception;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/lang/{lang}', function ($lang){
    if(!$lang){
        throw new Exception('Parameter is required');
    }
    $findLangFilePath = resource_path("lang/$lang/translation.json");

    if(File::exists("$findLangFilePath")){
        $json_data=File::get("$findLangFilePath");
        $response = Response::make($json_data, 200);
        $response->header('Content-Type', 'application/json');
        return $response;
    }else{
        throw new Exception('Language file does not found');
    }
});
