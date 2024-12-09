<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\CourseLabels;
use App\Models\CourseLangsNames;
use App\Models\CourseLocations;
use App\Models\Currencies;
use App\Models\Languages;
use App\Models\Roles;
use App\Models\SchoolLocations;
use App\Models\Schools;
use App\Models\TeachersCourse;
use App\Models\UserRoles;
use Database\Seeders\LanguageSelectSeeder;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class CourseController extends Controller
{
    public function index()
    {

    }
    public function create(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();

        $validator = Validator::make($request->all(), [
            "courseId"=>"nullable|exists:course_infos,id",
            "name"=>"required",
            "name.*.lang"=>"required",
            "name.*.name"=>"required",
            "studentLimit"=>"required|numeric|min:1",
            "minutesLesson"=>"required|numeric|min:1",
            "minTeachingDay"=>"required|numeric|min:1",
            "coursePricePerLesson"=>"required|numeric|min:1",
            "locationId"=>"required|exists:locations,id",
            "labels"=>"required",
            "paymentPeriod"=>"required",
            "status"=>"nullable",
            "currency"=>"required"
        ],[
            "name"=>__("validation.custom.name.required"),
            "studentLimit"=>__("validation.custom.studentLimit.required"),
            "studentLimit.min"=>__("validation.custom.studentLimit.min"),
            "minutesLesson"=>[__("validation.custom.minutesLesson.required"),__("validation.custom.minutesLesson.min")],
            "minutesLesson.min"=>__("validation.custom.minutesLesson.min"),
            "minTeachingDay"=>__("validation.custom.minTeachingDay.required"),
            "minTeachingDay.min"=> __("validation.custom.minTeachingDay.min"),
            "locationId"=>__("validation.custom.locationId.required"),
            "labels"=>__("validation.custom.labels.required"),
            "paymentPeriod"=>__("validation.custom.paymentPeriod.required"),
            "currency"=>__("validation.custom.currency.required")
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        foreach ($request->name as $n){
            if($n['lang'] === null || $n['name']=== null){
                throw new \Exception(__('messages.invalid.name'));
            }
        }

        if($request->courseId === null){

            try{
                $uniqueControlCourseInfo= CourseInfos::where([
                    "teacher_id" => $request->teacherId,
                ])->pluck('id');
                $uniqueControlCourseName = CourseLangsNames::whereIn('course_id', $uniqueControlCourseInfo)->where('name', $request->name)->exists();
                if($uniqueControlCourseName === false){
                    DB::transaction(function() use(&$request, $user){
                        $courseCreate=[
                            "student_limit" => $request->studentLimit,
                            "minutes_lesson" => $request->minutesLesson,
                            "min_teaching_day" => $request->minTeachingDay,
                            "course_price_per_lesson" => $request->coursePricePerLesson,
                            "course_status" => "ACTIVE",
                            "teacher_id" => $user->id,
                            "payment_period" => $request->paymentPeriod,
                            "currency"=>$request->currency
                        ];

                        $insertCourseData=CourseInfos::insertGetId($courseCreate);

                        $courseLocationData=[
                            "course_id"=>$insertCourseData,
                            "location_id"=>$request->locationId,
                        ];
                        CourseLocations::insert($courseLocationData);

                        TeachersCourse::create([
                            "teacher_id" => $user->id,
                            "course_id"=>$insertCourseData,
                            "payment_period"=>$request->paymentPeriod
                        ]);

                        $courseLangNameCreate=[];
                        foreach ($request->name as $n){
                            $courseLangNameCreate[]=[
                                "course_id"=>$insertCourseData,
                                "lang"=>$n['lang'],
                                "name"=>$n['name'],
                            ];
                        }

                        CourseLangsNames::insert($courseLangNameCreate);

                        $courseLabelsInsert=[];
                        foreach($request->labels as $label){
                            $courseLabelsInsert[]=[
                                "course_id"=>$insertCourseData, "label_id"=>$label['id']
                            ];
                        }
                        CourseLabels::insert($courseLabelsInsert);
                    });
                    return response(__("messages.success"));
                }else{
                    throw new \Exception(__("messages.unique.course"));
                }
            }catch(\Exception $e){
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw $e;
            }
        }else{
            $findCourse=CourseInfos::where("id", $request->courseId)->first();
            $findCourseLangsName=CourseLangsNames::where('course_id', $findCourse->id)->get();
            $findCourseLocation=CourseLocations::where("course_id", $findCourse->id)->first();
            if($findCourse){
                try{
                    DB::transaction(function() use($request, $findCourse, $findCourseLangsName, $findCourseLocation, $user){
                        $findCourse->update([
                            "student_limit" => $request->studentLimit,
                            "minutes_lesson" => $request->minutesLesson,
                            "min_teaching_day" => $request->minTeachingDay,
                            "course_price_per_lesson" => $request->coursePricePerLesson,
                            "status_id" => $request->status,
                            "school_year_id" => $request->yearId,
                            "teacher_id" => $user->id,
                            "payment_period" => $request->paymentPeriod,
                            "currency"=>$request->currency
                        ]);
                        $findCourseLocation->update([
                            "location_id" => $request->locationId,
                        ]);

                        foreach ($request->name as $n){
                            $findCourseLanguageDetails=CourseLangsNames::where([
                                "course_id"=>$n['course_id'],
                                "lang"=>$n['lang'],
                                "name"=>$n['name'],
                            ])->first();
                            if($findCourseLanguageDetails){
                                $findCourseLanguageDetails->update([
                                    "lang" => $n['lang'],
                                    "name" => $n['name'],
                                ]);
                            }else{
                                CourseLangsNames::create([
                                    "course_id" => $n['course_id'],
                                    "lang" => $n['lang'],
                                    "name" => $n['name'],
                                ]);
                            }
                        }
                    });
                }catch(\Exception $e){
                    event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                    throw $e;
                }
                try{
                    DB::transaction(function () use ($request){
                        $courseLabelsInsert=[];
                        foreach($request->labels as $label){
                            $findCourseLabel = CourseLabels::where(["course_id" => $request->courseId, "label_id" => $label['id']])->exists();
                            if(!$findCourseLabel){
                                $courseLabelsInsert[]=[
                                    "course_id"=>$request->courseId, "label_id"=>$label['id']
                                ];
                            }
                            CourseLabels::insert($courseLabelsInsert);
                        }
                    });
                }catch (\Exception $e){
                    throw $e;
                }
                return response(__("messages.success"));
            }else{
                throw new \Exception(__("messages.error"));
            }
        }
    }
    public function get($locationId){

        if(Permission::checkPermissionForTeachers("READ", null, null)){
            $user=JWTAuth::parseToken()->authenticate();
            $getTeacherCourse=CourseInfos::where('teacher_id', $user->id)->get();


            $courses=CourseInfos::where(['teacher_id'=>$user->id])->with('courseNamesAndLangs')->with('location')->get();
            $final=[];
            $select=[];
            if($locationId !== 'null'){
                $filter=$courses->filter(function ($c) use($locationId){
                     return $c->location?->id == $locationId;
                });
                $courses=$filter;
            }
            foreach ($courses as $course){
                $languages=[];
                foreach ($course->courseNamesAndLangs as $name) {
                    $languages[]=$name->lang;
                }
                $final[]=[
                    "id" => $course->id,
                    "name" => $course->courseNamesAndLangs[0]->name,
                    'student_limit'=>$course->student_limit,
                    'minutes_lesson'=>$course->minutes_lesson,
                    'min_teaching_day'=>$course->min_teaching_day,
                    'course_price_per_lesson'=>$course->course_price_per_lesson,
                    'status'=>$course->course_status,
                    'lang'=>$languages
                ];

                $select[]=[
                    "value"=>$course->id,
                    "label"=>$course->courseNamesAndLangs[0]->name
                ];
            }
            $tableHeader=[
                __("tableHeaders.id"),
                __("tableHeaders.name"),
                __("tableHeaders.language"),
                __("tableHeaders.status"),
            ];
            $success=[
                "header"=>$tableHeader,
                "courses"=>$final,
                "select"=>$select
            ];
            return response()->json($success,200);
        }
    }

    public function remove(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        $validator = Validator::make($request->all(), [
            "id"=>"required|exists:course_infos,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request, $user){
                CourseLabels::where('course_id', $request->id)->delete();
                CourseLangsNames::where('course_id', $request->id)->delete();
                CourseLocations::where('course_id', $request->id)->delete();
                TeachersCourse::where(["teacher_id"=>$user->id, "course_id"=>$request->id])->delete();
                CourseInfos::where("id", $request->id)->delete();

                //TODO: TeacherCourseRequests, CoursePayments
            });
        }catch(\Exception $e){
            event(new ErrorEvent($user,'Remove', '500', __("messages.error"), json_encode(debug_backtrace())));
            throw $e;
        }
        return response(__("messages.success"));
    }

    public function getCourseStatuses(){

        $courseStatuses=[
            [
                "value"=>"ACTIVE",
                "label"=>__("statuses.active")
            ],
            [
                "value"=>"SUSPENDED",
                "label"=>__("statuses.suspended")
            ],
            [
                "value"=>"DELETED",
                "label"=>__("statuses.delete")
            ],
        ];
        return response()->json($courseStatuses);
    }
    public function getPaymentPeriods(){
        $paymentPeriods=[
            [
                "value"=>"PER_LESSON",
                "label"=>__("statuses.per_lesson")
            ],
            [
                "value"=>"MONTHLY",
                "label"=>__("statuses.monthly")
            ],
            [
                "value"=>"HALF_YEAR",
                "label"=>__("statuses.half_year")
            ],
            [
                "value"=>"YEARLY",
                "label"=>__("statuses.yearly")
            ],
        ];
        return response()->json($paymentPeriods);
    }
    public function getCourseInfo($courseId){

        Validator::validate(["courseId"=>$courseId],[
            "courseId"=>"required|exists:course_infos,id"
        ]);
        $checkCourseLocation=CourseLocations::where("course_id", $courseId)->pluck('id');
        $course=CourseInfos::where(["id"=>$courseId])->first();

        if($course){
            $labels= $course->label()->get();
            $teacher=$course->teacher()->first();
            $teacherName= [
                "value"=>$teacher->id,
                "label"=>$teacher->first_name . ' ' . $teacher->last_name . ' (' . $teacher->email . ')'
            ];
            $location=$course->location()->first();
            $courseName= $course->courseNamesAndLangs()->get();
            $success=[
                "id"=>$course->id,
                "name"=>$courseName,
                'student_limit'=>$course->student_limit,
                'minutes_lesson'=>$course->minutes_lesson,
                'min_teaching_day'=>$course->min_teaching_day,
                'course_price_per_lesson'=>$course->course_price_per_lesson,
                'status'=>[
                    "value"=>$course->course_status,
                    "label"=>__("enums.$course->course_status")
                ],
                'labels'=>$labels,
                'teacher'=>$teacherName,
                'location'=>$location,
                'paymentPeriod'=>[
                    "value"=>$course->payment_period,
                    "label"=>__("enums.$course->payment_period")
                ],
                'currency'=>[
                    "value"=>$course->currency,
                    "label"=>$course->currency,
                ]
            ];
            return response()->json($success,200);
        }else{
            throw new \Exception(__("messages.error"));
        }

    }
    public function getRolesandSchools($userId){
        //TODO:A jogosultságot a kurzushoz kellene ellenőrizni, nem az iskolához. Erre sztem nem is lesz szükség.
        //if(Permission::checkPermissionForSchoolService("WRITE", 0)){
        $getAttachedRoles = UserRoles::where("user_id",$userId)->pluck("role_id")->toArray();
        $getRoles =Roles::all()->pluck('id')->toArray();
        if($getAttachedRoles){
            $notAttached = array_diff($getRoles, $getAttachedRoles);

            $roleNames=[];
            foreach($notAttached as $n){
                $result=Roles::where("id", $n)->first();
                $roleNames[]= [
                    "value"=>$result["name"],
                    "label"=>$result["name"]
                ];
            }

        }else{
            $getRoles =Roles::all();
        }

        $getSchools=Schools::all();

        if($getSchools){


            $finalSchool=[];

            foreach($getSchools as $s){
                $finalSchool[]=[
                    "value"=>$s['name'],
                    "label"=>$s['name']
                ];
            }
        }
        $success=[
            "roles"=>$roleNames,
            "schools"=>$finalSchool
        ];

        return response()->json($success);
        /*}else{
            throw new Exception("Denied");
        }*/
    }
    public function getTeachingDayNames(){
        $dayNames=[
            [
                "value"=>"MONDAY",
                "label"=>"Monday"
            ],
            [
                "value"=>"TUESDAY",
                "label"=>"Tuesday"
            ],
            [
                "value"=>"WEDNESDAY",
                "label"=>"Wednesday"
            ],
            [
                "value"=>"THURSDAY",
                "label"=>"Thursday"
            ],
            [
                "value"=>"FRIDAY",
                "label"=>"Friday"
            ],
            [
                "value"=>"SATURDAY",
                "label"=>"Saturday"
            ],
            [
                "value"=>"SUNDAY",
                "label"=>"Sunday"
            ],
        ];

        return response()->json($dayNames);
    }
    public function getCurrenciesISO(){
        $query=Currencies::all();
        $success=[];
        foreach ($query as $c){
            $success[]=[
                "value"=>$c->value,
                "label"=>$c->label
            ];
        }

        return response()->json($success);
    }

    public function getLanguages(){
        $query=Languages::all();
        return response()->json($query);
    }
    public function getCourseProfile($courseId){
        $validateCourseId=CourseInfos::where('id',$courseId)->exists();

        if(!$validateCourseId){
            throw new \Exception(__("messages.notFound.course"));
        }

        $getCourseInfos=CourseInfos::where(['id'=>$courseId, 'course_status' => "ACTIVE"])
            ->with('courseNamesAndLangs')
            ->with('teacher')
            ->with('location')
        ->firstOrFail();

        $success=[
            "id"=>$getCourseInfos->id,
            "minutes_lesson"=>$getCourseInfos->minutes_lesson,
            "min_teaching_day"=>$getCourseInfos->min_teaching_day,
            "course_price_per_lesson"=>$getCourseInfos->course_price_per_lesson,
            "payment_period"=>__("enums.$getCourseInfos->payment_period"),
            "currency"=>$getCourseInfos->currency,
            "teacher"=>$getCourseInfos->teacher,
            "location"=>$getCourseInfos->location,
            "course_names_and_langs"=>$getCourseInfos->courseNamesAndLangs
        ];
        return response()->json($success);
    }
}
