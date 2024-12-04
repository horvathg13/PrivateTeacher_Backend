<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\CourseLabels;
use App\Models\CourseLangsNames;
use App\Models\CourseLocations;
use App\Models\Roles;
use App\Models\SchoolLocations;
use App\Models\Schools;
use App\Models\TeachersCourse;
use App\Models\UserRoles;
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
            "studentLimit"=>"required",
            "minutesLesson"=>"required",
            "minTeachingDay"=>"required",
            "coursePricePerLesson"=>"required",
            "locationId"=>"required",
            "labels"=>"required",
            "paymentPeriod"=>"required",
            "status"=>"nullable",
            "currency"=>"required"
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
                /*if($request->locationId){
                    $checkCourseLocation=CourseLocations::where(["course_id"=> $request->courseId, "location_id" => $request->locationId])->first();
                    if(!$checkCourseLocation){
                        throw new \Exception(__('messages.invalid.location'));
                    }
                }*/

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
    public function get(){

        if(Permission::checkPermissionForTeachers("READ", null, null)){
            $user=JWTAuth::parseToken()->authenticate();
            $getTeacherCourse=CourseInfos::where('teacher_id', $user->id)->get();

            $courses=CourseInfos::where('teacher_id', $user->id)->with('courseNamesAndLangs')->get();

            $final=[];
            $select=[];
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
                "id",
                'name',
                'language',
                'status',
            ];
            $success=[
                "header"=>$tableHeader,
                "courses"=>$final,
                "select"=>$select
            ];
            return response()->json($success,200);
        }/*else if($childId){

            if(Permission::checkPermissionForParents("READ", $childId)){
               //TODO: A gyerek kurzusát lekérdezni.
            }
        }else{
            $tableHeader=[
                "id",
                'name',
                'language',
                'status',
            ];
            $success=[
                "header"=>$tableHeader,
                "courses"=>[__('messages.notFound.course')],
                "select"=>null
            ];

        }*/

        /*$getCourseLocationId=CourseLocations::where("course_id", $courseId)->pluck('id');
        $courses=CourseInfos::whereIn("school_location_id",$getCourseLocationId)->where("school_year_id",$schoolYearId)->with('courseNamesAndLangs')->get();
        $tableHeader=[
            "id",
            'name',
            'language',
            /*'subject',
            'student_limit',
            'minutes/lesson',
            'min_teaching_day',
            'double_time',
            'course_price_per_lesson',
            'status',
        ];*/

        /*if($courses){
            $final=[];

            foreach ($courses as $course){
                foreach ($course->courseNamesAndLangs as $name) {
                    $final[]=[
                        "id"=>$course->id,
                        'name'=>$name->name,
                        'student_limit'=>$course->student_limit,
                        'minutes_lesson'=>$course->minutes_lesson,
                        'min_teaching_day'=>$course->min_teaching_day,
                        'course_price_per_lesson'=>$course->course_price_per_lesson,
                        'status'=>$course->course_status,
                        'lang'=>$name->lang,
                    ];
                }
            }
            $select=[];
            foreach ($final as $course) {
                $select[]=[
                    "value"=>$course['id' ],
                    "label"=>$course['name']
                ];
            }

            $success=[
                "header"=>$tableHeader,
                "courses"=>$final,
                "select"=>$select
            ];
            return response()->json($success,200);
        }else{
            throw new \Exception(__("messages.error"));
        }*/

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
        return response()->json([
            ["value" => "AED", "label" => "AED"],
            ["value" => "AFN", "label" => "AFN"],
            ["value" => "ALL", "label" => "ALL"],
            ["value" => "AMD", "label" => "AMD"],
            ["value" => "ANG", "label" => "ANG"],
            ["value" => "AOA", "label" => "AOA"],
            ["value" => "ARS", "label" => "ARS"],
            ["value" => "AUD", "label" => "AUD"],
            ["value" => "AWG", "label" => "AWG"],
            ["value" => "AZN", "label" => "AZN"],
            ["value" => "BAM", "label" => "BAM"],
            ["value" => "BBD", "label" => "BBD"],
            ["value" => "BDT", "label" => "BDT"],
            ["value" => "BGN", "label" => "BGN"],
            ["value" => "BHD", "label" => "BHD"],
            ["value" => "BIF", "label" => "BIF"],
            ["value" => "BMD", "label" => "BMD"],
            ["value" => "BND", "label" => "BND"],
            ["value" => "BOB", "label" => "BOB"],
            ["value" => "BRL", "label" => "BRL"],
            ["value" => "BSD", "label" => "BSD"],
            ["value" => "BTN", "label" => "BTN"],
            ["value" => "BWP", "label" => "BWP"],
            ["value" => "BYN", "label" => "BYN"],
            ["value" => "BZD", "label" => "BZD"],
            ["value" => "CAD", "label" => "CAD"],
            ["value" => "CDF", "label" => "CDF"],
            ["value" => "CHF", "label" => "CHF"],
            ["value" => "CLP", "label" => "CLP"],
            ["value" => "CNY", "label" => "CNY"],
            ["value" => "COP", "label" => "COP"],
            ["value" => "CRC", "label" => "CRC"],
            ["value" => "CUC", "label" => "CUC"],
            ["value" => "CUP", "label" => "CUP"],
            ["value" => "CVE", "label" => "CVE"],
            ["value" => "CZK", "label" => "CZK"],
            ["value" => "DJF", "label" => "DJF"],
            ["value" => "DKK", "label" => "DKK"],
            ["value" => "DOP", "label" => "DOP"],
            ["value" => "DZD", "label" => "DZD"],
            ["value" => "EGP", "label" => "EGP"],
            ["value" => "ERN", "label" => "ERN"],
            ["value" => "ETB", "label" => "ETB"],
            ["value" => "EUR", "label" => "EUR"],
            ["value" => "FJD", "label" => "FJD"],
            ["value" => "FKP", "label" => "FKP"],
            ["value" => "FOK", "label" => "FOK"],
            ["value" => "GBP", "label" => "GBP"],
            ["value" => "GEL", "label" => "GEL"],
            ["value" => "GGP", "label" => "GGP"],
            ["value" => "GHS", "label" => "GHS"],
            ["value" => "GIP", "label" => "GIP"],
            ["value" => "GMD", "label" => "GMD"],
            ["value" => "GNF", "label" => "GNF"],
            ["value" => "GTQ", "label" => "GTQ"],
            ["value" => "GYD", "label" => "GYD"],
            ["value" => "HKD", "label" => "HKD"],
            ["value" => "HNL", "label" => "HNL"],
            ["value" => "HRK", "label" => "HRK"],
            ["value" => "HTG", "label" => "HTG"],
            ["value" => "HUF", "label" => "HUF"],
            ["value" => "IDR", "label" => "IDR"],
            ["value" => "ILS", "label" => "ILS"],
            ["value" => "IMP", "label" => "IMP"],
            ["value" => "INR", "label" => "INR"],
            ["value" => "IQD", "label" => "IQD"],
            ["value" => "IRR", "label" => "IRR"],
            ["value" => "ISK", "label" => "ISK"],
            ["value" => "JEP", "label" => "JEP"],
            ["value" => "JMD", "label" => "JMD"],
            ["value" => "JOD", "label" => "JOD"],
            ["value" => "JPY", "label" => "JPY"],
            ["value" => "KES", "label" => "KES"],
            ["value" => "KGS", "label" => "KGS"],
            ["value" => "KHR", "label" => "KHR"],
            ["value" => "KID", "label" => "KID"],
            ["value" => "KMF", "label" => "KMF"],
            ["value" => "KRW", "label" => "KRW"],
            ["value" => "KWD", "label" => "KWD"],
            ["value" => "KYD", "label" => "KYD"],
            ["value" => "KZT", "label" => "KZT"],
            ["value" => "LAK", "label" => "LAK"],
            ["value" => "LBP", "label" => "LBP"],
            ["value" => "LKR", "label" => "LKR"],
            ["value" => "LRD", "label" => "LRD"],
            ["value" => "LSL", "label" => "LSL"],
            ["value" => "LYD", "label" => "LYD"],
            ["value" => "MAD", "label" => "MAD"],
            ["value" => "MDL", "label" => "MDL"],
            ["value" => "MGA", "label" => "MGA"],
            ["value" => "MKD", "label" => "MKD"],
            ["value" => "MMK", "label" => "MMK"],
            ["value" => "MNT", "label" => "MNT"],
            ["value" => "MOP", "label" => "MOP"],
            ["value" => "MRU", "label" => "MRU"],
            ["value" => "MUR", "label" => "MUR"],
            ["value" => "MVR", "label" => "MVR"],
            ["value" => "MWK", "label" => "MWK"],
            ["value" => "MXN", "label" => "MXN"],
            ["value" => "MYR", "label" => "MYR"],
            ["value" => "MZN", "label" => "MZN"],
            ["value" => "NAD", "label" => "NAD"],
            ["value" => "NGN", "label" => "NGN"],
            ["value" => "NIO", "label" => "NIO"],
            ["value" => "NOK", "label" => "NOK"],
            ["value" => "NPR", "label" => "NPR"],
            ["value" => "NZD", "label" => "NZD"],
            ["value" => "OMR", "label" => "OMR"],
            ["value" => "PAB", "label" => "PAB"],
            ["value" => "PEN", "label" => "PEN"],
            ["value" => "PGK", "label" => "PGK"],
            ["value" => "PHP", "label" => "PHP"],
            ["value" => "PKR", "label" => "PKR"],
            ["value" => "PLN", "label" => "PLN"],
            ["value" => "PYG", "label" => "PYG"],
            ["value" => "QAR", "label" => "QAR"],
            ["value" => "RON", "label" => "RON"],
            ["value" => "RSD", "label" => "RSD"],
            ["value" => "RUB", "label" => "RUB"],
            ["value" => "RWF", "label" => "RWF"],
            ["value" => "SAR", "label" => "SAR"],
            ["value" => "SBD", "label" => "SBD"],
            ["value" => "SCR", "label" => "SCR"],
            ["value" => "SDG", "label" => "SDG"],
            ["value" => "SEK", "label" => "SEK"],
            ["value" => "SGD", "label" => "SGD"],
            ["value" => "SHP", "label" => "SHP"],
            ["value" => "SLE", "label" => "SLE"],
            ["value" => "SLL", "label" => "SLL"],
            ["value" => "SOS", "label" => "SOS"],
            ["value" => "SRD", "label" => "SRD"],
            ["value" => "SSP", "label" => "SSP"],
            ["value" => "STN", "label" => "STN"],
            ["value" => "SVC", "label" => "SVC"],
            ["value" => "SYP", "label" => "SYP"],
            ["value" => "SZL", "label" => "SZL"],
            ["value" => "THB", "label" => "THB"],
            ["value" => "TJS", "label" => "TJS"],
            ["value" => "TMT", "label" => "TMT"],
            ["value" => "TND", "label" => "TND"],
            ["value" => "TOP", "label" => "TOP"],
            ["value" => "TRY", "label" => "TRY"],
            ["value" => "TTD", "label" => "TTD"],
            ["value" => "TVD", "label" => "TVD"],
            ["value" => "TWD", "label" => "TWD"],
            ["value" => "TZS", "label" => "TZS"],
            ["value" => "UAH", "label" => "UAH"],
            ["value" => "UGX", "label" => "UGX"],
            ["value" => "USD", "label" => "USD"],
            ["value" => "UYU", "label" => "UYU"],
            ["value" => "UZS", "label" => "UZS"],
            ["value" => "VES", "label" => "VES"],
            ["value" => "VND", "label" => "VND"],
            ["value" => "VUV", "label" => "VUV"],
            ["value" => "WST", "label" => "WST"],
            ["value" => "XAF", "label" => "XAF"],
            ["value" => "XCD", "label" => "XCD"],
            ["value" => "XOF", "label" => "XOF"],
            ["value" => "XPF", "label" => "XPF"],
            ["value" => "YER", "label" => "YER"],
            ["value" => "ZAR", "label" => "ZAR"],
            ["value" => "ZMW", "label" => "ZMW"],
            ["value" => "ZWL", "label" => "ZWL"]
        ]);

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
