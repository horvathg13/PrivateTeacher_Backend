<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CommonRequests;
use App\Models\CourseInfos;
use App\Models\CourseLabels;
use App\Models\CourseLangsNames;
use App\Models\CourseLocations;
use App\Models\Currencies;
use App\Models\LabelLanguages;
use App\Models\Languages;
use App\Models\Roles;
use App\Models\SchoolLocations;
use App\Models\Schools;
use App\Models\StudentCourse;
use App\Models\StudentCourseTeachingDays;
use App\Models\TeacherCourseRequests;
use App\Models\TeachersCourse;
use App\Models\TerminationCourseRequests;
use App\Models\UserRoles;
use Database\Seeders\LanguageSelectSeeder;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;
use function MongoDB\BSON\toJSON;
use function PHPUnit\Framework\isNan;

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
            "name.*.lang"=>"required|exists:languages,value",
            "name.*.name"=>"required",
            "name.*.labels"=>"required",
            "studentLimit"=>"required|numeric|min:1",
            "minutesLesson"=>"required|numeric|min:1",
            "minTeachingDay"=>"required|numeric|min:1",
            "coursePricePerLesson"=>"required|numeric|min:0",
            "locationId"=>"required|exists:locations,id",
            "paymentPeriod"=>"required",
            "status"=>"nullable",
            "currency"=>"required",
            "start"=>"required|date",
            "end"=>"required|date|after:start"
        ],[
            "name"=>__("validation.custom.name.required"),
            "studentLimit"=>__("validation.custom.studentLimit.required"),
            "studentLimit.min"=>__("validation.custom.studentLimit.min"),
            "minutesLesson"=>__("validation.custom.minutesLesson.required"),
            "minutesLesson.min"=>__("validation.custom.minutesLesson.min"),
            "minTeachingDay"=>__("validation.custom.minTeachingDay.required"),
            "minTeachingDay.min"=> __("validation.custom.minTeachingDay.min"),
            "coursePricePerLesson"=>__("validation.custom.coursePricePerLesson.required"),
            "coursePricePerLesson.numeric"=>__("validation.custom.coursePricePerLesson.numeric"),
            "coursePricePerLesson.min"=>__("validation.custom.coursePricePerLesson.min"),
            "locationId"=>__("validation.custom.locationId.required"),
            "paymentPeriod"=>__("validation.custom.paymentPeriod.required"),
            "currency"=>__("validation.custom.currency.required"),
            "start.required"=>__("validation.custom.schoolYear.start.required"),
            "start.date"=>__("validation.custom.schoolYear.start.required"),
            "end.required"=>__("validation.custom.schoolYear.end.required"),
            "end.date"=>__("validation.custom.schoolYear.end.required"),
            "end.after"=>__("validation.custom.schoolYear.end.after"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $langs = array_column($request->name, 'lang');
        if (count($langs) !== count(array_unique($langs))) {
            throw new ControllerException(__('messages.invalid.name'),422);
        }
        foreach ($request->name as $n){

            if($n['lang'] === null || $n['name']=== null){
                throw new ControllerException(__('messages.invalid.name'));
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
                            "currency"=>$request->currency,
                            "start_date"=>$request->start,
                            "end_date"=>$request->end
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
                        foreach($request->name as $key => $value){
                            $getLanguageId=Languages::where('value', "=", $value['lang'])->pluck('id')->first();
                            foreach ($value['labels'] as $l) {
                                $courseLabelsInsert[]=[
                                    "course_id"=>$insertCourseData,
                                    "label_id"=>$l['id'],
                                    "language_id"=>$getLanguageId
                                ];
                            }
                        }
                        foreach ($courseLabelsInsert as $value){
                            CourseLabels::insert($value);
                        }

                    });
                    return response(__("messages.success"));
                }else{
                    throw new ControllerException(__("messages.unique.course"));
                }
            }catch(\Exception $e){
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.error"));
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
                            "course_status" => $request->status,
                            "teacher_id" => $user->id,
                            "payment_period" => $request->paymentPeriod,
                            "currency"=>$request->currency,
                            "start_date"=>$request->start,
                            "end_date"=>$request->end
                        ]);
                        $findCourseLocation->update([
                            "location_id" => $request->locationId,
                        ]);

                        foreach ($request->name as $n){
                            $findCourseLanguageDetails=CourseLangsNames::where([
                                "id"=>$n['id'],
                                "course_id"=>$n['course_id'],
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
                        if($request->remove){
                            foreach ($request->remove as $r){
                                CourseLangsNames::where('id',$r['id'])->delete();
                            }
                        }
                    });
                }catch(\Exception $e){
                    event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                    throw new ControllerException(__("messages.error"));
                }
                try{
                    DB::transaction(function () use ($request){
                        $courseLabelsInsert=[];
                        foreach($request->name as $key => $value){
                            $getLanguageId=Languages::where('value', "=", $value['lang'])->pluck('id')->first();
                            foreach ($value['labels'] as $l) {
                                if(CourseLabels::where(["course_id" => $request->courseId, "label_id" => $l['id'], "language_id" => $getLanguageId])->doesntExist()){
                                    $courseLabelsInsert[]=[
                                        "course_id"=>$request->courseId,
                                        "label_id"=>$l['id'],
                                        "language_id"=>$getLanguageId
                                    ];
                                }
                            }
                        }
                        foreach ($courseLabelsInsert as $value){
                            CourseLabels::insert($value);
                        }
                    });
                }catch (\Exception $e){
                    throw new ControllerException(__("messages.error"));
                }
                return response(__("messages.success"));
            }else{
                throw new ControllerException(__("messages.error"));
            }
        }
    }
    public function get($locationId){
        $user=JWTAuth::parseToken()->authenticate();

        if($user->isTeacher()){
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
                    'lang'=>$languages,
                    'start'=>$course->start_date,
                    'end'=>$course->end_date,
                ];

                $select[]=[
                    "value"=>$course->id,
                    "label"=>$course->courseNamesAndLangs[0]->name
                ];
            }
            $tableHeader=[
                "id","name","language","status"
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
            });
        }catch(\Exception $e){
            event(new ErrorEvent($user,'Remove', '500', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.error"));
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

        $validation=Validator::make(["courseId"=>$courseId],[
            "courseId"=>"required|numeric|exists:course_infos,id"
        ],[
            "courseId.required"=>__("validation.custom.courseId.required"),
            "courseId.numeric"=>__("validation.custom.courseId.numeric"),
            "courseId.exists"=>__("validation.custom.courseId.exists")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
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
            $finalCourseName=[];
            $courseName= $course->courseNamesAndLangs()->get()->map(function ($name) use(&$finalCourseName){
                $getLanguageId=Languages::where("value", "=", $name->lang)->pluck('id')->first();
                $getCourseLabels=CourseLabels::where(['course_id'=> $name->course_id, "language_id" => $getLanguageId])->with('getCourseLabels')->get();
                $finalCourseName[]=[
                    "lang"=>$name->lang,
                    "course_id"=>$name->course_id,
                    "name"=>$name->name,
                    "labels"=>$getCourseLabels->map(function (CourseLabels $labels){
                        return $labels->getCourseLabels;
                    }),
                    "id"=>$name->id,
                ];
            });
            $success=[
                "id"=>$course->id,
                "name"=>$finalCourseName,
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
                ],
                'start'=>$course->start_date,
                'end'=>$course->end_date,
            ];
            return response()->json($success,200);
        }else{
            throw new ControllerException(__("messages.error"));
        }

    }
    public function getTeachingDayNames(){
        $dayNames=[
            [
                "value"=>"MONDAY",
                "label"=>'enums.MONDAY'
            ],
            [
                "value"=>"TUESDAY",
                "label"=>'enums.TUESDAY'
            ],
            [
                "value"=>"WEDNESDAY",
                "label"=>'enums.WEDNESDAY'
            ],
            [
                "value"=>"THURSDAY",
                "label"=>'enums.THURSDAY'
            ],
            [
                "value"=>"FRIDAY",
                "label"=>'enums.FRIDAY'
            ],
            [
                "value"=>"SATURDAY",
                "label"=>'enums.SATURDAY'
            ],
            [
                "value"=>"SUNDAY",
                "label"=>'enums.SUNDAY'
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

        $validation=Validator::make(["courseId"=>$courseId],[
            "courseId"=>"required|numeric|exists:course_infos,id",
            "childId"=>"nullable|numeric|exists:children,id"
        ],[
            "courseId.required"=>__("validation.custom.courseId.required"),
            "courseId.numeric"=>__("validation.custom.courseId.numeric"),
            "courseId.exists"=>__("validation.custom.courseId.exists"),
            "childId.numeric"=>__("validation.custom.childId.numeric"),
            "childId.exists"=>__("validation.custom.childId.exists"),
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $validateCourseId=CourseInfos::where('id',$courseId)->exists();

        if(!$validateCourseId){
            throw new ControllerException(__("messages.notFound.course"));
        }

        $getCourseInfos=CourseInfos::where(['id'=>$courseId, 'course_status' => "ACTIVE"])
            ->with('courseNamesAndLangs')
            ->with('teacher')
            ->with('location')
        ->firstOrFail();
        $findLanguages=[];
        foreach ($getCourseInfos->courseNamesAndLangs as $item) {
            $findLanguages[]=Languages::where('value',$item->lang)->first();
        }
        $success=$this->commonCourseProfile($courseId);
        return response()->json($success);
    }

    public function getStudentCourseProfile($childId,$studentCourseId)
    {
        $validation=Validator::make(["courseId"=>$studentCourseId],[
            "courseId"=>"required|numeric|exists:student_course,id",
            "childId"=>"nullable|numeric|exists:children,id"
        ],[
            "courseId.required"=>__("validation.custom.courseId.required"),
            "courseId.numeric"=>__("validation.custom.courseId.numeric"),
            "courseId.exists"=>__("validation.custom.courseId.exists"),
            "childId.numeric"=>__("validation.custom.childId.numeric"),
            "childId.exists"=>__("validation.custom.childId.exists"),
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $validateCourseId=StudentCourse::where('id',$studentCourseId)->first();

        if(!$validateCourseId){
            throw new ControllerException(__("messages.notFound.course"));
        }

        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForParents("WRITE", $childId)){
            return $this->commonStudentCourseProfile($studentCourseId);
        }

        if(Permission::checkPermissionForTeachers("WRITE", $validateCourseId->teacher_course_id,null )){
            return $this->commonStudentCourseProfile($studentCourseId);
        }

        return [
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace()))),
            throw new ControllerException(__("messages.denied.permission"))
        ];
    }

    public function commonStudentCourseProfile($studentCourseId){
        $isActiveStudentCourse=false;
        $haveTerminationRequest=false;
        $getTimeTableInfos=[];

        $isStudentCourse= StudentCourse::where("id", "=", $studentCourseId)->first();

        $getCourseInfos=CourseInfos::where(['id'=>$isStudentCourse->teacher_course_id])->first();
        if($getCourseInfos){
            $isActiveStudentCourse=$isStudentCourse->end_date === $getCourseInfos->end_date;
            if($isActiveStudentCourse){
                $haveTerminationRequest=TerminationCourseRequests::where("student_course_id", $isStudentCourse->id)->exists();
            }
            $getTimeTableInfos=StudentCourseTeachingDays::where("student_course_id", $isStudentCourse->id)->get();
        }
        $success=[
            "isActiveStudentCourse"=>$isActiveStudentCourse,
            "haveTerminationRequest"=>$haveTerminationRequest,
            "timetableInfo"=>$getTimeTableInfos,
            "data"=>$this->commonCourseProfile($getCourseInfos->id)
        ];
        return response()->json($success);
    }


    public function commonCourseProfile($courseId){
        $getCourseInfos=CourseInfos::where(['id'=>$courseId, 'course_status' => "ACTIVE"])
            ->with('courseNamesAndLangs')
            ->with('teacher')
            ->with('location')
        ->firstOrFail();
        $findLanguages=[];
        foreach ($getCourseInfos->courseNamesAndLangs as $item) {
            $findLanguages[]=Languages::where('value',$item->lang)->first();
        }

        return [
            "id"=>$getCourseInfos->id,
            "minutes_lesson"=>$getCourseInfos->minutes_lesson,
            "min_teaching_day"=>$getCourseInfos->min_teaching_day,
            "course_price_per_lesson"=>$getCourseInfos->course_price_per_lesson,
            "payment_period"=>$getCourseInfos->payment_period,
            "currency"=>$getCourseInfos->currency,
            "teacher"=>$getCourseInfos->teacher,
            "location"=>$getCourseInfos->location,
            "course_names_and_langs"=>$getCourseInfos->courseNamesAndLangs,
            "languages"=>$findLanguages,
            "start"=>$getCourseInfos->start_date,
            "end"=>$getCourseInfos->end_date,
        ];
    }

    public function getStudentList($CourseId){
        $validation=Validator::make(["courseId"=>$CourseId],[
            "courseId"=>"required|numeric|exists:course_infos,id",
        ],[
            "courseId.required"=>__("validation.custom.courseId.required"),
            "courseId.numeric"=>__("validation.custom.courseId.numeric"),
            "courseId.exists"=>__("validation.custom.courseId.exists"),
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $studentCourse=StudentCourse::where('teacher_course_id', $CourseId)->pluck('teacher_course_request_id');
        $getTeacherCourseRequests=TeacherCourseRequests::whereIn('id', $studentCourse)->with('childInfo')->get();

        $data=[];
        $tableHeader=["id", "firstname", "lastname", "birthdate"];
        foreach ($getTeacherCourseRequests as $request){
            $data[]=[
                "id"=>$request->childInfo->id,
                "first_name"=>$request->childInfo->first_name,
                "last_name"=>$request->childInfo->last_name,
                "birthday"=>$request->childInfo->birthday
            ];
        }
        $success=[
            "header"=>$tableHeader,
            "data"=>$data
        ];
        return response()->json($success);
    }
}
