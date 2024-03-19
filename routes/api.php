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
Route::post('/createUser',[App\Http\Controllers\AuthController::class, 'createUser']);
Route::get('/password-reset/{token}',[App\Http\Controllers\AuthController::class, 'passwordReset']);
Route::post('/resetPassword',[App\Http\Controllers\AuthController::class, 'resetPassword']);

Route::post('/getUsers',[App\Http\Controllers\APIController::class, 'getUsers']);
Route::post('/getRoles',[App\Http\Controllers\APIController::class, 'getRoles']);
Route::post('/getUserStatuses',[App\Http\Controllers\APIController::class, 'getUserStatuses']);
Route::post('/updateUser',[App\Http\Controllers\APIController::class, 'UpdateUser']);
Route::get('/selectedUserData/{userId}',[App\Http\Controllers\APIController::class, 'getSelectedUserData']);
Route::post('/schoolCreate',[App\Http\Controllers\APIController::class, 'SchoolCreate']);
Route::post('/schools-list',[App\Http\Controllers\APIController::class, 'SchoolList']);
Route::get('/school/{schoolId}',[App\Http\Controllers\APIController::class, 'getSchoolInfo']);
Route::post('/schoolUpdate',[App\Http\Controllers\APIController::class, 'SchoolUpdate']);
Route::post('/school-year-list/{schoolId}',[App\Http\Controllers\APIController::class, 'getSchoolYears']);
Route::post('/createSchoolYear',[App\Http\Controllers\APIController::class, 'createSchoolYear']);
Route::post('/removeSchoolYear',[App\Http\Controllers\APIController::class, 'removeSchoolYear']);
Route::get('/school/{schoolId}/school-year-details/{schoolYearId}',[App\Http\Controllers\APIController::class, 'getSchoolYearDetails']);
Route::get('/school/{schoolId}/school-year-infos/{schoolYearId}',[App\Http\Controllers\APIController::class, 'getSchoolYearInfos']);
Route::post('/createSchoolBreak',[App\Http\Controllers\APIController::class, 'createSchoolBreak']);
Route::post('/createSpecialWorkDay',[App\Http\Controllers\APIController::class, 'createSpecialWorkDay']);
Route::post('/removeSchoolBreak',[App\Http\Controllers\APIController::class, 'removeSchoolBreak']);
Route::post('/removeSpecialWorkDay',[App\Http\Controllers\APIController::class, 'removeSpecialWorkDay']);
Route::get('/school/{schoolId}/school-year-details/{schoolYearId}/courses',[App\Http\Controllers\APIController::class, 'getSchoolCourses']);
Route::post('/createSchoolCourse',[App\Http\Controllers\APIController::class, 'createSchoolCourse']);
Route::post('/removeSchoolCourse',[App\Http\Controllers\APIController::class, 'removeSchoolCourse']);
Route::get('/school/{schoolId}/school-year-details/{schoolYearId}/courses/{courseId}',[App\Http\Controllers\APIController::class, 'getSchoolCourseInfo']);
Route::post('/getCourseStatuses',[App\Http\Controllers\APIController::class, 'getSchoolCourseStatuses']);
Route::post('/getUserRoles/{userId}',[App\Http\Controllers\APIController::class, 'getUserRoles']);
Route::post('/removeUserRole/{userId}/{roleId}/{referenceId}',[App\Http\Controllers\APIController::class, 'removeUserRole']);
Route::post('/getRolesandSchools/{userId}',[App\Http\Controllers\APIController::class, 'getRolesandSchools']);
Route::post('/createUserRole',[App\Http\Controllers\APIController::class, 'createUserRole']);
Route::post('/createChild',[App\Http\Controllers\APIController::class, 'createChild']);
Route::post('/connectToChild',[App\Http\Controllers\APIController::class, 'connectToChild']);
Route::get('/getConnectedChildren',[App\Http\Controllers\APIController::class, 'getConnectedChildren']);
Route::post('/searchTeacher',[App\Http\Controllers\APIController::class, 'searchTeacher']);
Route::post('/searchSchoolCourse',[App\Http\Controllers\APIController::class, 'searchSchoolCourse']);
Route::post('/createLabel',[App\Http\Controllers\APIController::class, 'createLabel']);
Route::post('/searchLabel',[App\Http\Controllers\APIController::class, 'searchLabel']);
