<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    APIController,
    AuthController,
    UserController,
    SearchController,
    SchoolController,
    ChildController,
    CourseController,
    LocationController,
    RequestsController,
    MessagesController,
    NotificationController
};
use App\Http\Middleware\{
    AdminRightMiddleware,
    ParentMiddleware,
    TeacherMiddleware
};

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

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
    Route::get('/password-reset/{token}', 'passwordReset');
    Route::post('/resetPassword', 'resetPassword');
    Route::post('/createUser', 'createUser');
});

/*UserController*/
Route::controller(UserController::class)->group(function () {
    Route::post('/getUserData', 'getUserData');
    Route::post('/getUsers', 'getUsers');
    Route::post('/getRoles', 'getRoles');
    Route::post('/getAllRoles', 'getGlobalRoles');
    Route::post('/getUserStatuses', 'getUserStatuses');

    Route::middleware([AdminRightMiddleware::class])->group(function () {
        Route::post('/updateUser', 'UpdateUser');
        Route::get('/selectedUserData/{userId}', 'getSelectedUserData');
        Route::post('/getUserRoles/{userId}', 'getUserRoles');
        Route::post('/removeUserRole/{userId}/{roleId}', 'removeUserRole');
        Route::post('/createUserRole', 'createUserRole');
    });
});

/*SchoolController*/
Route::controller(SchoolController::class)->group(function () {
    Route::post('/schoolCreate', 'SchoolCreate');
    Route::post('/schools-list', 'SchoolList');
    Route::get('/school/{schoolId}', 'getSchoolInfo');
    Route::post('/schoolUpdate', 'SchoolUpdate');
    Route::post('/school-year-list/{schoolId}', 'getSchoolYears');
    Route::post('/getSchoolYearStatuses', 'getSchoolYearStatuses');
    Route::post('/createSchoolYear', 'createSchoolYear');
    Route::post('/removeSchoolYear', 'removeSchoolYear');
    Route::get('/school/{schoolId}/school-year-details/{schoolYearId}', 'getSchoolYearDetails');
    Route::get('/school/{schoolId}/school-year-infos/{schoolYearId}', 'getSchoolYearInfos');
    Route::post('/createSchoolBreak', 'createSchoolBreak');
    Route::post('/createSpecialWorkDay', 'createSpecialWorkDay');
    Route::post('/removeSchoolBreak', 'removeSchoolBreak');
    Route::post('/removeSpecialWorkDay', 'removeSpecialWorkDay');
    Route::get('/school/{schoolId}/school-year-details/{schoolYearId}/courses', 'getSchoolCourses');
    Route::post('/createSchoolCourse', 'createSchoolCourse');
    Route::post('/removeSchoolCourse', 'removeSchoolCourse');
    Route::get('/school/{schoolId}/school-year-details/{schoolYearId}/courses/{courseId}', 'getSchoolCourseInfo');
    Route::post('/getCourseStatuses', 'getSchoolCourseStatuses');
    Route::post('/getRolesandSchools/{userId}', 'getRolesandSchools');
    Route::post('/createSchoolLocation', 'createSchoolLocation');
    Route::post('/getSchoolLocations', 'getSchoolLocations');
    Route::post("/getSchoolLocation", "getSchoolLocation");
    Route::post("/removeSchoolLocation", "removeSchoolLocation");
    Route::post('/getSchoolTeachers', "getSchoolTeachers");
    Route::post('/getPaymentPeriods', "getPaymentPeriods");
    Route::post('/getTeachingDayNames', "getTeachingDayNames");
    Route::post('/createTeachingDay', 'createTeachingDay');
    Route::post('/getTeachingDays', 'getTeachingDays');
});

/*CourseController*/
Route::controller(CourseController::class)->group(function () {
    Route::get('/getCourses', 'get');
    Route::post('/getCourseStatuses', 'getCourseStatuses');
    Route::post('/getPaymentPeriods', 'getPaymentPeriods');
    Route::get('/getCourseInfo/{courseId}', 'getCourseInfo');
    Route::get('/getCurrenciesISO', 'getCurrenciesISO');
    Route::get('/getCourseProfile/{courseId}', "getCourseProfile");

    Route::middleware([TeacherMiddleware::class])->group(function () {
        Route::post('/createCourse', 'create');
        Route::post('/removeCourse', 'remove');
        Route::post('/getTeachingDayNames', 'getTeachingDayNames');
    });
});

/*LocationController*/
Route::controller(LocationController::class)->group(function () {
    Route::post('/getCourseLocations', 'getCourseLocations');
    Route::post('/getLocations', 'getLocations');
    Route::get('/getLocationInfo/{locationId}', 'getLocationInfo');

    Route::middleware([TeacherMiddleware::class])->group(function () {
        Route::post('/createLocation', 'create');
        Route::post('/removeCourseLocation', 'removeCourseLocation');
    });

});
/*ChildController*/
Route::controller(ChildController::class)->group(function () {
    Route::middleware([ParentMiddleware::class])->group(function (){
        Route::post('/createChild', 'createChild');
        Route::post('/connectToChild', 'connectToChild');
        Route::get('/getConnectedChildren', 'getConnectedChildren');
        Route::get('/getChildInfo/{childId}', 'getChildInfo');
        Route::post('/updateChildInfo', 'updateChildInfo');
        Route::post('/getChildren', 'getChildSelect');
        Route::post('/sendCourseRequest', 'sendCourseRequest');
        Route::get('getChildCourses/{childId}', 'getChildCourses');
    });
});

/*SearchController*/
Route::controller(SearchController::class)->group(function () {
    Route::post('/searchTeacher', 'searchTeacher');
    Route::post('/searchSchool', 'searchSchool');
    Route::post('/createLabel', 'createLabel');
    Route::post('/searchLabel', 'searchLabel');
    Route::post('/searchCourse', 'searchCourse');
});

/*Request Controller*/
Route::controller(RequestsController::class)->group(function () {
    Route::get('/getRequests', 'get');
    Route::post('/getRequestDetails', 'getRequestDetails');

    Route::middleware([TeacherMiddleware::class])->group(function () {
        Route::post('/acceptCourseRequest', 'accept');
        Route::post('/rejectCourseRequest', 'reject');
    });
});


/*MessagesController*/
Route::controller(MessagesController::class)->group(function () {
    Route::get('/getMessages', 'get');
    Route::get('/getMessageInfo/{messageId}', 'getMessageInfo');
    Route::post('/sendMessage','sendMessage');
});

/*Notifications*/
Route::controller(NotificationController::class)->group(function () {
    Route::get('/getNotifications', 'get');
    Route::get('/haveUnreadNotifications', 'haveUnreadNotifications');
    Route::get('/readNotification/{id}', 'readNotification');
});
