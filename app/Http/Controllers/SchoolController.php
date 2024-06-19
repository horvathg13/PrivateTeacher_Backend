<?php

namespace App\Http\Controllers;

use App\Helper\Permission;
use App\Models\CourseInfos;
use App\Models\CourseLabels;
use App\Models\CourseLangsNames;
use App\Models\Locations;
use App\Models\Roles;
use App\Models\SchoolBreaks;
use App\Models\SchoolLocations;
use App\Models\Schools;
use App\Models\SchoolTeachers;
use App\Models\SchoolYears;
use App\Models\SpecialWorkDays;
use App\Models\Statuses;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SchoolController extends Controller
{
    public function index()
    {

    }
    public function SchoolCreate(Request $request){
        $user = JWTAuth::parsetoken()->authenticate();
        //Role based AccessControl...
        $validator = Validator::make($request->all(), [
            "name"=>"required",
            "country"=>"required",
            "zip"=>"required",
            "city"=>"required",
            "street"=>"required",
            "number"=>"required",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        try{
            DB::transaction(function () use ($request){
                $createSchool=Schools::create([
                    "name"=>$request->name,
                    "country"=>$request->country,
                    "zip"=>$request->zip,
                    "city"=>$request->city,
                    "street"=>$request->street,
                    "number"=>$request->number
                ]);
            });
        }catch (\Exception $e){
            throw $e;
        }

        return response()->json(["message"=>__("messages.success")],200);
    }


    public function SchoolList(Request $request){
        $list = Schools::paginate($request->perPage ?: 5);


        if(!$list){
            throw new \Exception(__("messages.notFound.school"));
        }

        $paginator=[
            "currentPageNumber"=>$list->currentPage(),
            "hasMorePages"=>$list->hasMorePages(),
            "lastPageNumber"=>$list->lastPage(),
            "total"=>$list->total(),
        ];
        $tableData=[];
        foreach($list as $l){
            $tableData[]=[
                "id"=>$l->id,
                "name"=>$l->name,
                "country"=>$l->country,
                "city"=>$l->city,
                "zip"=>$l->zip,
                "street"=>$l->street,
                "number"=>$l->number,
            ];
        }
        $tableHeader=[
            "id"=>true,
            "name"=>true,
            "country"=>true,
            "city"=>true,
            "zip"=>true,
            "street"=>false,
            "number"=>false,
        ];

        $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
            "pagination"=>$paginator
        ];
        return response()->json($success);
    }

    public function getSchoolInfo($schoolId){
        Validator::validate(["schoolId"=>$schoolId],[
            "schoolId"=>"required|exists:schools,id"
        ]);

        $school=Schools::where('id', $schoolId)->first();

        return response()->json($school);
    }

    public function SchoolUpdate(Request $request){

        $validator = Validator::make($request->all(), [
            "id"=>"required|exists:schools,id",
            "name"=>"required",
            "city"=>"required",
            "zip"=>"required",
            "street"=>"required",
            "number"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(Permission::checkPermissionForSchoolService("WRITE",$request->id)){
            $findSchool=Schools::find($request->id)->first();
            if($findSchool){
                DB::transaction(function () use ($request, $findSchool){

                    $findSchool->update([
                        "name"=>$request->name,
                        "city"=>$request->city,
                        "zip"=>$request->zip,
                        "street"=>$request->street,
                        "number"=>$request->number
                    ]);


                });
                return response()->json(["message"=>__("messages.success")]);

            }
        }else{
            throw new \Exception(__("messages.denied.role"));
        }
    }

    public function getSchoolYears(Request $request){
        $years = SchoolYears::where("school_id", $request->schoolId)->get();


        $tableData=[];
        if($years){
            foreach($years as $year){
                $tableData[]=[
                    "id"=>$year->id,
                    "year"=>$year->year,
                    "name"=>$year->name,
                    "start"=>$year->start,
                    "end"=>$year->end,
                ];
            }
        }

        $tableHeader=[
            "id"=>false,
            'year'=>false,
            'name'=>false,
            'start'=>false,
            'end'=>false,
        ];


        $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
        ];
        return response()->json($success);
    }

    public function getSchoolYearStatuses(){
        $get=[
            [
                "value"=>"ACTIVE",
                "label"=>__('statuses.active')
            ],
            [
                "value"=>"SUSPENDED",
                "label"=>__("statuses.suspended")
            ],
            [
                "value"=>"DELETED",
                "label"=>__("statuses.delete")
            ]
        ];

        return response()->json($get);
    }

    public function createSchoolYear(Request $request){
        if(Permission::checkPermissionForSchoolService("WRITE",$request->schoolId)){
            $validator = Validator::make($request->all(), [
                "schoolYear"=>"required",
                "name"=>"required",
                "startDate"=>"required",
                "endDate"=>"required|after:startDate",
                "id"=>"nullable",
                "status"=>"required"
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            if($request->id != null){
                $findSchoolYear= SchoolYears::where(["id"=> $request->id, "school_id" => $request->schoolId])->first();

                DB::transaction(function () use ($request, $findSchoolYear){

                    $findSchoolYear->update([
                        "year"=>$request->schoolYear,
                        "name"=>$request->name,
                        "start" => $request->startDate,
                        "end" => $request->endDate,
                        "year_status"=>$request->status,
                    ]);

                });
            }else{
                DB::transaction(function () use ($request){

                    SchoolYears::create([
                        "year"=>$request->schoolYear,
                        "school_id"=>$request->schoolId,
                        "name"=>$request->name,
                        "start" => $request->startDate,
                        "end" => $request->endDate,
                        "year_status"=>$request->status,
                    ]);
                });
            }
            return response()->json([__("messages.success")],200);
        }else{
            throw new \Exception(__("messages.denied.role"));
        }
    }

    public function removeSchoolYear(Request $request){
        if(Permission::checkPermissionForSchoolService("WRITE",$request->schoolId)){
            $validator = Validator::make($request->all(), [
                "schoolId"=>"required|exists:schools,id",
                "yearId"=>"required|exists:school_years,id",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            try{
                DB::transaction(function () use ($request){

                    SchoolYears::where(["school_id"=>$request->schoolId, "id"=>$request->yearId])->delete();

                });
            }catch (Exception $e){
                throw $e;
            }


            return response()->json(__("messages.success"));
        }else{
            throw new \Exception(__("messages.denied.role"));
        }

    }
    public function getSchoolYearInfos($schoolId, $schoolYearId){
        Validator::validate(["schoolId"=>$schoolId, "schoolYearId"=>$schoolYearId],[
            "schoolId"=>"required|exists:schools,id",
            "schoolYearId"=>"required|exists:school_years,id"
        ]);

        $SchoolYearDetails = SchoolYears::where("id", $schoolYearId)->first();
        $schoolInfos = Schools::where("id", $schoolId)->first();

        $success=[
            "year"=>$SchoolYearDetails->year,
            "name"=>$SchoolYearDetails->name,
            "start"=>$SchoolYearDetails->start,
            "end"=>$SchoolYearDetails->end,
            "status"=>$SchoolYearDetails->year_status,
            "schoolName"=>$schoolInfos->name,
            "schoolId"=>$schoolInfos->id
        ];
        return response()->json($success, 200);
    }
    public function getSchoolYearDetails($schoolId, $schoolYearId){
        $breaks = SchoolBreaks::where(["school_id"=> $schoolId, "school_year_id"=> $schoolYearId])->get();

        $specWorkDays = SpecialWorkDays::where(["school_id"=> $schoolId, "school_year_id"=>$schoolYearId])->get();

        $tableHeader=[
            "id",
            "name",
            "start",
            "end",
        ];

        $success=[
            "header"=>$tableHeader,
            "breaks"=>$breaks,
            "specialWorkDays"=>$specWorkDays,
        ];
        return response()->json($success);
    }
    public function createSchoolBreak(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();

        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "name"=>"required",
            "start"=>"required",
            "end"=>"required",
            "id"=>"nullable"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(!$request->id){
            $checkAlreadyExists=SchoolBreaks::where(["school_id"=>$request->schoolId, "school_year_id" => $request->yearId, "start"=>$request->start, "end"=>$request->end])->exists();
            if($checkAlreadyExists){
                throw new \Exception(__("messages.attached.exists"));
            }
            try{
                DB::transaction(function () use($request){
                    SchoolBreaks::create([
                        "name"=>$request->name,
                        "start"=>$request->start,
                        "end"=>$request->end,
                        "school_id"=>$request->schoolId,
                        "school_year_id"=>$request->yearId
                    ]);
                });
            }catch(\Exception $e){
                throw $e;
            }
            return response(__("messages.success"));
        }else{
            $findId= SchoolBreaks::where("id", $request->id)->first();

            if($findId){
                try{
                    DB::transaction(function() use($request, $findId){
                        $findId->update([
                            "name"=>$request->name,
                            "start"=>$request->start,
                            "end"=>$request->end,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
                }catch(\Exception $e){
                    throw $e;
                }

                return response(__("messages.success"));
            }
        }
    }
    public function createSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();

        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "name"=>"required",
            "start"=>"required",
            "end"=>"required",
            "id"=>"nullable"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(!$request->id){
            $checkAlreadyExists=SpecialWorkDays::where(["school_id"=>$request->schoolId, "school_year_id" => $request->yearId, "start"=>$request->start, "end"=>$request->end])->exists();
            if($checkAlreadyExists){
                throw new \Exception(__("messages.attached.exists"));
            }
            try{
                DB::transaction(function () use($request){
                    SpecialWorkDays::create([
                        "name"=>$request->name,
                        "start"=>$request->start,
                        "end"=>$request->end,
                        "school_id"=>$request->schoolId,
                        "school_year_id"=>$request->yearId
                    ]);
                });
            }catch(\Exception $e){
                throw $e;
            }
            return response(__("messages.success"));
        }else{
            $findId= SpecialWorkDays::where("id", $request->id)->first();

            if($findId){
                try{
                    DB::transaction(function() use($request, $findId){
                        $findId->update([
                            "name"=>$request->name,
                            "start"=>$request->start,
                            "end"=>$request->end,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
                }catch(\Exception $e){
                    throw $e;
                }

                return response(__("messages.success"));
            }
        }

    }

    public function removeSchoolBreak(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:school_breaks,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                SchoolBreaks::where("id", $request->id)->delete();
            });
        }catch(\Exception $e){
            throw $e;
        }
        return response(__("messages.success"));

    }

    public function removeSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:special_work_days,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                SpecialWorkDays::where("id", $request->id)->delete();
            });
        }catch(\Exception $e){
            throw $e;
        }
        return response(__("messages.success"));

    }

    public function createSchoolCourse(Request $request){
        /*$user= JWTAuth::parsetoken()->authenticate();*/

        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "courseId"=>"nullable|exists:course_infos,id",
            "name"=>"required",
            "studentLimit"=>"required",
            "minutesLesson"=>"required",
            "minTeachingDay"=>"required",
            "doubleTime"=>"nullable",
            "coursePricePerLesson"=>"required",
            "status"=>"required",
            "locationId"=>"required",
            "labels"=>"required",
            "teacherId"=>"required|exists:users,id",
            "paymentPeriod"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        if($request->courseId === null){

            try{
                $getTeacherRole=Roles::where("name", "Teacher")->first();
                $validTeacher = UserRoles::where(["user_id"=>$request->teacherId, "role_id" => $getTeacherRole->id, "reference_id" => $request->schoolId])->exists();
                if(!$validTeacher){
                    throw new \Exception(__('messages.denied.teacher'));
                }
                $checkSchoolLocation=SchoolLocations::where(["school_id"=> $request->schoolId, "location_id" => $request->locationId])->first();

                $uniqueControlCourseInfo= CourseInfos::where([
                    "school_location_id"=>$checkSchoolLocation->id,
                    "school_year_id"=>$request->yearId,
                    "teacher_id" => $request->teacherId,
                ])->pluck('id');
                $uniqueControlCourseName = CourseLangsNames::whereIn('course_id', $uniqueControlCourseInfo)->where('name', $request->name)->exists();
                if($uniqueControlCourseName === false){
                    DB::transaction(function() use(&$request, $checkSchoolLocation){
                        $courseCreate=[
                            "student_limit" => $request->studentLimit,
                            "minutes_lesson" => $request->minutesLesson,
                            "min_teaching_day" => $request->minTeachingDay,
                            "double_time" => $request->doubleTime ?: false,
                            "course_price_per_lesson" => $request->coursePricePerLesson,
                            "course_status" => $request->status,
                            "school_location_id" => $checkSchoolLocation->id,
                            "school_year_id" => $request->yearId,
                            "teacher_id" => $request->teacherId,
                            "payment_period" => $request->paymentPeriod
                        ];

                        $insertCourseData=CourseInfos::insertGetId($courseCreate);
                        $courseLangNameCreate=[];
                        foreach ($request->name as $n){
                            $courseLangNameCreate[]=[
                                "course_id"=>$insertCourseData,
                                "lang"=>$n['lang'],
                                "name"=>$n['name'],
                            ];
                        }

                        CourseLangsNames::insert($courseLangNameCreate);
                    });
                }else{
                    throw new \Exception(__("messages.unique.course"));
                }
            }catch(\Exception $e){
                throw $e;
            }
            try{
                $findCourse = CourseInfos::where([
                    "school_location_id"=>$checkSchoolLocation->id,
                    "school_year_id"=>$request->yearId,
                    "teacher_id" => $request->teacherId,
                ])->first();

                if($findCourse){
                    DB::transaction(function () use ($request, $findCourse){
                        $courseLabelsInsert=[];
                        foreach($request->labels as $label){
                            $courseLabelsInsert[]=[
                                "course_id"=>$findCourse['id'], "label_id"=>$label['id']
                            ];
                        }
                        CourseLabels::insert($courseLabelsInsert);
                    });
                }
            }catch (\Exception $e){
                throw $e;
            }
            return response(__("messages.success"));
        }else{
            $findCourse=CourseInfos::where("id", $request->courseId)->first();
            $findCourseLangsName=CourseLangsNames::where('course_id', $findCourse->id)->first();
            $findSchoolLocation=SchoolLocations::where("id", $findCourse->school_location_id)->first();
            if($findCourse){
                try{

                    DB::transaction(function() use($request, $findCourse, $findCourseLangsName, $findSchoolLocation){
                        $findCourse->update([
                            "student_limit" => $request->studentLimit,
                            "minutes/lesson" => $request->minutesLesson,
                            "min_teaching_day" => $request->minTeachingDay,
                            "double_time" => $request->doubleTime,
                            "course_price_per_lesson" => $request->coursePricePerLesson,
                            "status_id" => $request->status,
                            "school_year_id" => $request->yearId,
                            "teacher_id" => $request->teacherId,
                            "payment_period" => $request->paymentPeriod
                        ]);
                        $findSchoolLocation->update([
                            "location_id" => $request->locationId,
                        ]);

                        foreach ($request->name as $n){
                            $findN = $findCourseLangsName->where("lang",$n['lang'])->orWhere( "name",$n['name'])->first();
                            if(!$findN){
                                CourseLangsNames::create([
                                    "course_id"=>$findCourse->id,
                                    "lang"=>$n['lang'],
                                    "name"=>$n['name'],
                                ]);
                            }else{
                                $findN->update([
                                    "lang"=>$n['lang'],
                                    "name"=>$n['name'],
                                ]);
                            }
                        }

                    });

                }catch(\Exception $e){
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

    public function getSchoolCourses($schoolId, $schoolYearId){

        $getSchoolLocationId=SchoolLocations::where("school_id", $schoolId)->pluck('id');
        $courses=CourseInfos::whereIn("school_location_id",$getSchoolLocationId)->where("school_year_id",$schoolYearId)->with('courseNamesAndLangs')->get();
        $tableHeader=[
            "id",
            'name',
            'language',
            /*'subject',
            'student_limit',
            'minutes/lesson',
            'min_teaching_day',
            'double_time',
            'course_price_per_lesson',*/
            'status',
        ];

        if($courses){
            $final=[];

            foreach ($courses as $course){
                foreach ($course->courseNamesAndLangs as $name) {
                    $final[]=[
                        "id"=>$course->id,
                        'name'=>$name->name,
                        'student_limit'=>$course->student_limit,
                        'minutes_lesson'=>$course->minutes_lesson,
                        'min_teaching_day'=>$course->min_teaching_day,
                        'double_time'=>$course->double_time,
                        'course_price_per_lesson'=>$course->course_price_per_lesson,
                        'status'=>$course->course_status,
                        'lang'=>$name->lang,
                    ];
                }
            }

            $success=[
                "header"=>$tableHeader,
                "courses"=>$final
            ];
            return response()->json($success,200);
        }else{
            throw new \Exception(__("messages.error"));
        }

    }

    public function removeSchoolCourse(Request $request){
        /*$user= JWTAuth::parsetoken()->authenticate();*/
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:course_infos,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                CourseInfos::where("id", $request->id)->delete();
            });
        }catch(\Exception $e){
            throw $e;
        }
        return response(__("messages.success"));
    }

    public function getSchoolCourseStatuses(){

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
    public function getSchoolCourseInfo($schoolId, $schoolYearId, $courseId){

        Validator::validate(["schoolId"=>$schoolId, "schoolYearId"=>$schoolYearId, "courseId"=>$courseId],[
            "schoolId"=>"required|exists:schools,id",
            "schoolYearId"=>"required|exists:school_years,id",
            "courseId"=>"required|exists:course_infos,id"
        ]);
        $checkSchoolLocation=SchoolLocations::where("school_id", $schoolId)->pluck('id');
        $course=CourseInfos::whereIn("school_location_id",$checkSchoolLocation)->where(["school_year_id"=>$schoolYearId, "id"=>$courseId])->first();

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
                'double_time'=>$course->double_time,
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
                ]
            ];
            return response()->json($success,200);
        }else{
            throw new \Exception(__("messages.error"));
        }

    }
    public function getRolesandSchools($userId){
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

    public function createSchoolLocation(Request $request){
        $validation = Validator::make($request->all(),[
            "name"=>"required",
            "country"=>"required",
            "zip"=>"required",
            "city"=>"required",
            "street"=>"required",
            "number"=>"required",
            "floor"=>"nullable",
            "door"=>"nullable",
            "schoolId"=>"required|exists:schools,id",
            "locationId"=>"nullable|exists:locations,id"
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(!$request->locationId) {

            $checkLocation = Locations::where([
                "name" => $request->name,
                "country" => $request->country,
                "city" => $request->city,
                "zip" => $request->zip,
                "street" => $request->street,
                "number" => $request->number,
                "floor" => $request->floor,
                "door" => $request->door
            ])->first();
            if (!empty($checkLocation)) {
                $areadyAttachedToSchool = SchoolLocations::where("location_id", $checkLocation->id)->exists();

                if ($areadyAttachedToSchool) {
                    throw new \Exception(__("messages.attached.location"));
                }
            }

            if (empty($checkLocation)) {
                try {
                    DB::transaction(function () use (&$request) {
                        $newLocation = Locations::create([
                            "name" => $request->name,
                            "country" => $request->country,
                            "city" => $request->city,
                            "zip" => $request->zip,
                            "street" => $request->street,
                            "number" => $request->number,
                            "floor" => $request->floor,
                            "door" => $request->door
                        ]);
                        SchoolLocations::create([
                            "school_id" => $request->schoolId,
                            "location_id" => $newLocation->id,
                            "name" => $request->name
                        ]);
                    });
                } catch (\Exception $e) {
                    throw $e;
                }
            } else {
                SchoolLocations::create([
                    "school_id" => $request->schoolId,
                    "location_id" => $checkLocation->id,
                    "name" => $request->name,
                ]);
            }
            return response()->json(__("messages.success"), 200);
        }else{
            $getLocation= Locations::where("id", $request->locationId)->first();
            if(!empty($getLocation)){
                $getLocation->update([
                    "name" => $request->name,
                    "country" => $request->country,
                    "city" => $request->city,
                    "zip" => $request->zip,
                    "street" => $request->street,
                    "number" => $request->number,
                    "floor" => $request->floor,
                    "door" => $request->door
                ]);
            }else{
                throw new \Exception(__("messages.error"));
            }
            return response()->json(__("messages.success"),200);
        }
    }
    public function getSchoolLocations(Request $request){
        $validator=$request->validate([
            "schoolId"=>"required|exists:schools,id"
        ]);
        $header=[
            'id',
            'name',
            'country',
            'city',
            'zip',
            'street',
            'number',
            "floor",
            "door",
        ];
        $checkLocations=SchoolLocations::where("school_id", $request->schoolId)->exists();
        if(!$checkLocations){
            $notFound=[
                "message"=>__("messages.notFound.location"),
                "header"=>$header
            ];
            return response()->json($notFound, 200);
        }
        $getSchoolLocations= Schools::with('location')->find($request->schoolId);

        $data=[];
        $select=[];
        foreach ($getSchoolLocations->location as $i){
            $data[]=[
                "id"=>$i->id,
                "name"=>$i->name,
                "country"=>$i->country,
                "city"=>$i->city,
                "zip"=>$i->city,
                'street'=>$i->street,
                'number'=>$i->number,
                "floor"=>$i->floor,
                "door"=>$i->door,
            ];
            $select[]=[
                "value"=>$i->id,
                "label"=>$i->name,
            ];
        }
        $success=[
            "header"=>$header,
            "data"=>$data,
            "select"=>$select
        ];

        return response()->json($success);
    }
    public function getSchoolLocation(Request $request){
        $validator=$request->validate([
            "schoolId"=>"required|exists:schools,id",
            "locationId"=>"required|exists:locations,id"
        ]);
        $validateSchoolLocation=SchoolLocations::where(["location_id"=> $request->locationId, "school_id"=>$request->schoolId])->exists();

        if($validateSchoolLocation===true){
            $getLocationData=Locations::where("id", $request->locationId)->first();
            return response()->json($getLocationData);
        }else{
            throw new \Exception(__("messages.error"));
        }
    }
    public function removeSchoolLocation(Request $request){
        $validator=$request->validate([
            "schoolId"=>"required|exists:schools,id",
            "locationId"=>"required|exists:locations,id"
        ]);
        $validateSchoolLocation=SchoolLocations::where(["location_id"=> $request->locationId, "school_id"=>$request->schoolId])->first();
        if(!empty($validateSchoolLocation)){
            $validateSchoolLocation->delete();
            return response()->json(__("messages.detached.location"));
        }else{
            throw new \Exception(__("messages.error"));
        }
    }

    public function getSchoolTeachers(Request $request){
        $validate=Validator::make($request->all(),[
            "schoolId"=>"required|exists:schools,id"
        ]);
        if($validate->fails()){
            return response()->json($validate->errors());
        }
        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForSchoolService("WRITE", $request->schoolId)){
            $getTeacherRoleId=Roles::where("name", "Teacher")->first();
            $getTeachers=UserROles::where(["reference_id"=> $request->schoolId, "role_id"=>$getTeacherRoleId->id])->pluck("user_id");
            $list = User::whereIn("id", $getTeachers)->paginate($request->perPage ?: 5);

            if(empty($list)){
                throw new \Exception(__("messages.notFound.user"));
            }
            //dd($getTeachers);
            $paginator=[
                "currentPageNumber"=>$list->currentPage(),
                "hasMorePages"=>$list->hasMorePages(),
                "lastPageNumber"=>$list->lastPage(),
                "total"=>$list->total(),
            ];
            $tableData=[];
            $select=[];
            foreach($list as $l){
                $tableData[]=[
                    "id"=>$l->id,
                    "fname"=>$l->first_name,
                    "lname"=>$l->last_name,
                    "email"=>$l->email,
                ];
                $select[]=[
                    "value"=>$l->id,
                    "label"=>$l->first_name . ' ' . $l->last_name . ' (' . $l->email . ')'
                ];
            }
            $tableHeader=[
                "id"=>true,
                "firstname"=>true,
                "lastname"=>true,
                "email"=>true,
            ];

            $success=[
                "data"=>$tableData,
                "header"=>$tableHeader,
                "pagination"=>$paginator,
                "select"=>$select
            ];
            return response()->json($success);
        }
    }

}
