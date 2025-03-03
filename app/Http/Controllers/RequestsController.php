<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Helper\Student;
use App\Models\ChildrenConnections;
use App\Models\CommonRequests;
use App\Models\CourseInfos;
use App\Models\Messages;
use App\Models\Notifications;
use App\Models\StudentCourse;
use App\Models\StudentCourseTeachingDays;
use App\Models\TeacherCourseRequests;
use App\Models\TerminationCourseRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Symfony\Component\String\b;

class RequestsController extends Controller
{
    public function get(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $status = $request->status ?: 'UNDER_REVIEW';

        if (Permission::checkPermissionForTeachers("READ", null, null)) {
            $getCourses = CourseInfos::where('teacher_id', $user->id)->pluck("id");
            if ($getCourses) {
                $getCourseRequests = TeacherCourseRequests::whereIn('teacher_course_id', $getCourses)->pluck('id');
                $getStudentCourses = StudentCourse::whereIn('teacher_course_id', $getCourses)->pluck('id');
                $getTerminationRequests = TerminationCourseRequests::whereIn('student_course_id', $getStudentCourses)->pluck('id');

                $getCommonRequests = CommonRequests::where(function ($query) use ($getCourseRequests, $getTerminationRequests) {
                    $query->whereIn('requestable_id', $getCourseRequests);
                    $query->orWhereIn("requestable_id", $getTerminationRequests);
                })->where('status', "=", $status)
                    ->get();

                return $this->getCommonRequests($getCommonRequests);
            }else{
                return response()->json([]);
            }
        }
        if (Permission::checkPermissionForParents("READ", null)) {
            $getChildren = ChildrenConnections::where(['parent_id' => $user->id])->pluck('child_id');
            if($getChildren){
                return $this->getRequestsForParents($getChildren);
            }else {
                return response()->json([]);
            }
        }
    }

    public function getRequestsForParents($getChildren, $status=null)
    {
            $getChildrenCourseRequests = TeacherCourseRequests::whereIn('child_id', $getChildren)->pluck('id');
            $getStudentCourseIds = StudentCourse::whereIn('child_id', $getChildren)->pluck('id');
            $getChildTerminationRequests = TerminationCourseRequests::whereIn('student_course_id', $getStudentCourseIds)->pluck('id');

            $getCommonRequests = CommonRequests::where(function ($query) use ($getChildrenCourseRequests, $getChildTerminationRequests, $status) {
                $query->whereIn('requestable_id', $getChildrenCourseRequests);
                $query->orWhereIn("requestable_id", $getChildTerminationRequests);
            })->when($status, function ($q) use(&$status){
                $q->where("status", "=", $status);
            })->get();


            return $this->getCommonRequests($getCommonRequests);
    }

    public function getRequestsByChildId($childId){
        if(Permission::checkPermissionForParents("WRITE", $childId)){
            $user = JWTAuth::parseToken()->authenticate();

            return $this->getRequestsForParents([$childId]);

        }else{
            throw new ControllerException("message.permisssion.denied",403);
        }
    }

    public function getCommonRequests($commonRequests)
    {
        $finalData=[];
        foreach ($commonRequests as $r) {
            if ($r->requestable_type === "App\Models\TeacherCourseRequests") {
                TeacherCourseRequests::where('id', $r->requestable_id)
                    ->with('childInfo')
                    ->with('parentInfo')
                    ->with('courseNamesAndLangs')
                    ->with('request')
                    ->orderBy('created_at', 'asc')
                    ->each(function (TeacherCourseRequests $item) use (&$finalData, $r) {
                        $finalData[] = [
                            "id" => $r->id,
                            "number_of_lessons" => $item->number_of_lessons,
                            "notice" => $item->request->message,
                            "created_at" => $item->created_at,
                            "updated_at" => $item->updated_at,
                            "status" => $item->request->status,
                            "child_info" => $item->childInfo,
                            "course_names_and_langs" => array_filter($item->courseNamesAndLangs->toArray(), function ($c) use($item){
                                return $c['lang'] === $item->language;
                            }),
                            "type" => "APPLY"
                        ];
                    });
            }
            if ($r->requestable_type === "App\Models\TerminationCourseRequests") {
                TerminationCourseRequests::where('termination_course_requests.id', $r->requestable_id)->
                join('student_course', 'termination_course_requests.student_course_id', "=", "student_course.id")->
                join('course_infos', 'student_course.teacher_course_id', "=", "course_infos.id")->
                join('course_langs_names', 'course_infos.id', "=", "course_langs_names.course_id")
                    ->with('childInfo')
                    ->with('courseNamesAndLangs')
                    ->select('termination_course_requests.*',)
                    ->each(function (TerminationCourseRequests $t) use (&$finalData, $r) {
                        $finalData[] = [
                            "id" => $r->id,
                            "status" => $t->request()->pluck('status')->first(),
                            "child_info" => $t->childInfo,
                            "course_names_and_langs" => $t->courseNamesAndLangs,
                            "type" => "TERMINATION",
                            "created_at" => $t->created_at,
                            "updated_at" => $t->updated_at,
                        ];
                    });
            }
        }
        $tableHeader = [
            "id", "name", "course_name", "request_date", "status", "type"
        ];
        $arrayUnique = array_values(array_unique($finalData, SORT_REGULAR));
        $success = [
            "data" => $arrayUnique,
            "header" => $tableHeader,
        ];

        return response()->json($success);

    }

    public function getRequestDetails(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|numeric|exists:common_requests,id",
        ],[
            "requestId.required"=>__("validation.custom.requestId.required"),
            "requestId.numeric"=>__("validation.custom.requestId.numeric"),
            "requestId.exists"=>__("validation.custom.requestId.exists"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        $findCommonRequest=CommonRequests::where('id', $request->requestId)->first();
        if($findCommonRequest->requestable_type === 'App\Models\TerminationCourseRequests'){
            $getTerminationCourseRequest=TerminationCourseRequests::where('id',$findCommonRequest->requestable_id)->with('request')->first();

            $getStudentCourseInfo=StudentCourse::where('id', $getTerminationCourseRequest->student_course_id)
                ->with('courseInfos')
                ->with('parentInfo')
                ->with('childInfo')
                ->with('courseNamesAndLangs')
                ->first();
            $success = [
                "id" => $getStudentCourseInfo->teacher_course_request_id,
                "child_info" => $getStudentCourseInfo->childInfo,
                "parent_info" => $getStudentCourseInfo->parentInfo,
                "course_info" => $getStudentCourseInfo->courseInfos,
                "start_date"=>$getStudentCourseInfo->start_date,
                "end_date"=>$getStudentCourseInfo->end_date,
                "lang"=>$getStudentCourseInfo->language,
                "status"=>$getTerminationCourseRequest->request()->pluck('status')->first(),
                "course_names_and_langs" => $getStudentCourseInfo->courseNamesAndLangs,
                "terminationDetails"=>$getTerminationCourseRequest,
            ];
            return response()->json($success);
        }
        if($findCommonRequest->requestable_type === 'App\Models\TeacherCourseRequests') {
            $getRequestInfo = TeacherCourseRequests::where('id', $findCommonRequest->requestable_id)
                ->with("childInfo")
                ->with('parentInfo')
                ->with('courseInfo')
                ->with('courseNamesAndLangs')
                ->with("request")
            ->first();

            $success = [
                "id" => $getRequestInfo->id,
                "number_of_lessons" => $getRequestInfo->number_of_lessons,
                "requested_start_date" => $getRequestInfo->start_date,
                "notice" => $getRequestInfo->request->message,
                "created_at" => $getRequestInfo->created_at,
                "updated_at" => $getRequestInfo->updated_at,
                "status" => $getRequestInfo->request->status,
                "teacher_justification" => $getRequestInfo->teacher_justification,
                "lang"=>$getRequestInfo->language,
                "child_info" => $getRequestInfo->childInfo,
                "parent_info" => $getRequestInfo->parentInfo,
                "course_info" => $getRequestInfo->courseInfo,
                "course_names_and_langs" => $getRequestInfo->courseNamesAndLangs
            ];
            return response()->json($success);
        }
        throw new ControllerException(__("messages.error"));
    }

    public function accept(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|numeric|exists:common_requests,id",
            "message"=>"nullable|max:255",
            "start"=>"required|date",
            "teaching_day_details"=>"nullable|array",
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
            "start.required"=>__("validation.custom.courseRequest.start.required"),
            "start.date"=>__("validation.custom.courseRequest.start.date"),
            "teaching_day_details.required"=>__("validation.custom.teaching_day_details.required"),
            "teaching_day_details.required_array_keys"=>__("validation.custom.teaching_day_details.required_array_keys"),
            "teaching_day_details.required_array_keys.from"=>__("validation.custom.from.required"),
            "teaching_day_details.required_array_keys.to"=>__("validation.custom.to.required"),
            "teaching_day_details.required_array_keys.teaching_day"=>__("validation.custom.teaching_day.required"),
            "requestId.required"=>__("validation.custom.requestId.required"),
            "requestId.numeric"=>__("validation.custom.requestId.numeric"),
            "requestId.exists"=>__("validation.custom.requestId.exists"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $user=JWTAuth::parseToken()->authenticate();

        $findCommonRequest=CommonRequests::where('id', $request->requestId)->first();

        if($findCommonRequest->requestable_type === 'App\Models\TeacherCourseRequests') {

            if(!$request->message){
                throw new ControllerException(__("validation.custom.message.required"));
            }
            if(!$request->teaching_day_details){
                throw new ControllerException(__("validation.custom.teaching_day_details.required"));
            }

            $startTime=null;
            $days=[];
            foreach ($request->teaching_day_details as $e){

                if($e['to'] <= $e['from']){
                    throw new ControllerException(__("validation.custom.to.after"));
                }
                $days[]=$e['teaching_day'];
            }

            $isUnique=count($request->teaching_day_details) === count(array_unique($request->teaching_day_details, SORT_REGULAR));
            if(!$isUnique){
                throw new ControllerException(__('validation.custom.teaching_day_details.teaching_day.unique'));
            }
            $duplicatedDays=array_filter($days, function($d) use($days){
                return count(array_keys($days, $d)) > 1;
            });
            $finalDuplicatedDays=array_unique($duplicatedDays);
            foreach($request->teaching_day_details as $d){
                if(in_array($d['teaching_day'], $finalDuplicatedDays)){
                    if($startTime && $startTime >= $d['from'] && $startTime <= $d['to']){
                        throw new ControllerException(__('validation.custom.intervals.overlap'));
                    }
                    $startTime=$d['from'];
                }else {
                    $startTime = $d['from'];
                }
            }

            $getRequestCourseId = TeacherCourseRequests::where('id', $findCommonRequest->requestable_id)->pluck('teacher_course_id')->first();

            if (Permission::checkPermissionForTeachers('WRITE', $getRequestCourseId, null)) {
                $findRequest = TeacherCourseRequests::where('id', $findCommonRequest->requestable_id)->with('parentInfo')->first();
                $validateStudentCourse = StudentCourse::where([
                    "child_id" => $findRequest->child_id,
                    "teacher_course_id" => $findRequest->teacher_course_id
                ])->where("end_date", ">=", $request->start)->exists();
                if ($validateStudentCourse) {
                    throw new ControllerException(__("messages.attached.exists"), 409);
                }
                if ($findRequest) {
                    $getCourseEndDate = CourseInfos::where("id", $findRequest->teacher_course_id)->pluck('end_date')->first();
                    if ($request->start && $getCourseEndDate) {
                        $validateDates = $request->start <= $getCourseEndDate;
                        if (!$validateDates) {
                            $validatorResponse = [
                                "validatorResponse" => [__("messages.error")]
                            ];
                            return response()->json($validatorResponse, 422);
                        }
                    }
                    $studentLimit = Student::checkLimit($findRequest->teacher_course_id, $request->start, $getCourseEndDate);
                    if ($studentLimit['message'] === "error") {
                        $studentLimit['goodDate'] ?
                            throw new ControllerException(__("messages.studentLimit.goodDay", ["goodDay" => $studentLimit['goodDate']]))
                            : throw new ControllerException(__("messages.studentLimit.null"));
                    }
                    DB::transaction(function () use ($request, $findRequest, $user, &$getCourseEndDate, &$findCommonRequest) {
                        try {
                            $findCommonRequest->update([
                                "status" => "ACCEPTED",
                                "updated_at" => now(),
                            ]);
                            $findRequest->update([
                                "teacher_justification" => $request->message
                            ]);
                            $createStudentCourse = StudentCourse::insertGetId([
                                "teacher_course_request_id" => $findRequest->id,
                                "child_id" => $findRequest->child_id,
                                "teacher_course_id" => $findRequest->teacher_course_id,
                                "start_date" => $request->start,
                                "end_date" => $getCourseEndDate,
                                "created_at" => now(),
                                "updated_at" => now(),
                                "language"=>$findRequest->language
                            ]);
                            foreach ($findRequest->parentInfo as $parent) {
                                Notifications::create([
                                        "receiver_id" => $parent->id,
                                        "message" => "messages.notification.accepted",
                                        "url" => "/requests/".$findCommonRequest->id
                                ]);
                            }

                            foreach ($request->teaching_day_details as $e) {
                                StudentCourseTeachingDays::insert([
                                    "student_course_id" => $createStudentCourse,
                                    "teaching_day" => $e['teaching_day'],
                                    "from" => $e['from'],
                                    "to" => $e['to']
                                ]);
                            }

                        } catch (\Exception $e) {
                            event(new ErrorEvent($user, 'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                        }
                    });

                    return response()->json(__('messages.success'));
                }
            }
        }
        if($findCommonRequest->requestable_type === 'App\Models\TerminationCourseRequests'){
            $findTerminationCourseRequest=TerminationCourseRequests::where('id',$findCommonRequest->requestable_id)->first();
            $findStudentCourse=StudentCourse::where('id', $findTerminationCourseRequest->student_course_id)
                ->with('courseInfos')
                ->with('parentInfo')
            ->first();

            if($findStudentCourse->courseInfos->end_date < $request->start){
                throw new ControllerException(__("validation.custom.termination.before"));
            }
            try{
                DB::transaction(function() use($findCommonRequest, $findTerminationCourseRequest, $findStudentCourse, $request){
                    $findCommonRequest->update([
                        "status" => "ACCEPTED",
                        "updated_at" => now(),
                    ]);
                    $findStudentCourse->update([
                        "end_date" =>$request->start
                    ]);
                    foreach ($findStudentCourse->parentInfo as $parent) {
                        Notifications::create([
                            "receiver_id" => $parent->id,
                            "message" =>"messages.notification.terminationAccepted",
                            "url" => "/requests/" . $findCommonRequest->id,
                        ]);
                    }
                });
            }catch (\Exception $e){
                event(new ErrorEvent($user, 'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
            }
            return response()->json(__('messages.success'));
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.role'),403);
    }
    public function reject(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:common_requests,id",
            "message"=>"nullable|max:255"
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $findCommonRequest=CommonRequests::where('id', $request->requestId)->first();

        if($findCommonRequest->requestable_type === 'App\Models\TeacherCourseRequests') {

            $getRequestCourseId = TeacherCourseRequests::where('id', $findCommonRequest->requestable_id)->pluck('teacher_course_id')->first();

            if (Permission::checkPermissionForTeachers('WRITE', $getRequestCourseId, null)) {
                $findRequest = TeacherCourseRequests::where('id', $findCommonRequest->requestable_id)->with('parentInfo')->first();

                if ($findRequest) {
                    try {
                        DB::transaction(function () use ($request, $findRequest, $user, $findCommonRequest) {
                            $findCommonRequest->update([
                                "status" => "REJECTED",
                                "updated_at" => now()
                            ]);
                            foreach ($findRequest->parentInfo as $parent) {
                                Notifications::create([
                                    "receiver_id" => $parent->id,
                                    "message" => "messages.notification.rejected",
                                    "url" => "/requests/" . $findCommonRequest->id,
                                ]);
                            }
                        });
                    } catch (\Exception $e) {
                        event(new ErrorEvent($user, 'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                    }

                    return response()->json(__('messages.success'));
                }
            }
        }
        if($findCommonRequest->requestable_type === 'App\Models\TerminationCourseRequests'){
            $findTerminationCourseRequest=TerminationCourseRequests::where('id', $findCommonRequest->requestable_id)->first();
            $findStudentCourse=StudentCourse::where('id', $findTerminationCourseRequest->student_course_id)
                ->with('courseInfos')
                ->with('parentInfo')
            ->first();
            if(Permission::checkPermissionForTeachers('WRITE', $findStudentCourse->courseInfos->id, null)) {
                try {
                    DB::transaction(function () use ($findCommonRequest, $findStudentCourse, $request) {
                        $findCommonRequest->update([
                            "status" => "REJECTED",
                            "updated_at" => now(),
                        ]);
                        foreach ($findStudentCourse->parentInfo as $parent) {
                            Notifications::create([
                                "receiver_id" => $parent->id,
                                "message" => "messages.notification.terminationRejected",
                                "url" => "/requests/" . $findCommonRequest->id,
                            ]);
                        }
                    });
                } catch (\Exception $e) {
                    event(new ErrorEvent($user, 'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                }
                return response()->json(__('messages.success'));
            }
        }

        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.role'),403);
    }
    public function getChildRequests($childId){
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('WRITE', $childId)){
            $getStudentCourse=StudentCourse::where('child_id', $childId)->where(function ($query){
                $query->where('end_date', '>=', now());
            })->with('courseNamesAndLangs')->get();
            $success=[];
            foreach ($getStudentCourse as $studentCourse) {
                $success[]=[
                    "value"=>$studentCourse->id,
                    "label"=>$studentCourse->courseNamesAndLangs->where("lang", "=", $studentCourse->language)->pluck("name")->first()
                ];
            }

            return response()->json($success);
        }
        if(Permission::checkPermissionForTeachers("READ", null,null)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, "course_status"=>"ACTIVE"])->pluck('id');
            $getRequests=StudentCourse::whereIn('teacher_course_id',$getTeacherCourses)
                ->where('child_id',$childId)
                ->with('childInfo')
                ->with('courseNamesAndLangs')
            ->get();
            $success=[];
            foreach ($getRequests as $request) {
                $success[]=[
                    "value"=>$request->id,
                    "label"=>$request->courseNamesAndLangs[0]->name
                ];
            }
            return response()->json($success);
        }
    }
    public function terminationOfCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "student_course_id"=>"required|numeric|exists:student_course,id",
            "message"=>"required|max:255",
            "from"=>"required|date",
            "childId"=>"required|numeric|exists:children,id"
        ],[
            "student_course_id.required"=>__("validation.custom.studentCourse.required"),
            "student_course_id.numeric"=>__("validation.custom.studentCourse.numeric"),
            "student_course_id.exists"=>__("validation.custom.studentCourse.exists"),
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
            "from.required"=>__("validation.custom.from.required"),
            "from.date"=>__("validation.custom.from.date"),
            "childId.required" => __("validation.custom.childId.required"),
            "childId.exists" => __("validation.custom.childId.exists"),
            "childId.numeric" => __("validation.custom.childId.numeric"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $validateAlreadyTerminatedCourse=TerminationCourseRequests::where('student_course_id', $request->student_course_id)
            ->with('request')->get();

        foreach($validateAlreadyTerminatedCourse as $r){
            if($r->request->status === 'ACCEPTED'){
                throw new ControllerException(__("messages.attached.exists"));
            };
        };

        $getCourseInfos=StudentCourse::where('id',$request->student_course_id)->with("courseInfos")->first();
        $startDate=$getCourseInfos->courseInfos->start_date;
        $endDate=$getCourseInfos->courseInfos->end_date;
        if($getCourseInfos && $startDate && $endDate){
            $validateStartDate= $request->from > $getCourseInfos->start_date && $request->from < $getCourseInfos->end_date;
            if(!$validateStartDate){
                $validatorResponse=[
                    "validatorResponse"=>[__('messages.error')]
                ];
                return response()->json($validatorResponse,422);
            }
        }
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents("WRITE", $request->childId)){
           $validateStudentCourse=StudentCourse::where(["id"=>$request->student_course_id,"child_id" => $request->childId])->with('courseInfos')->first();

           if($validateStudentCourse){
               try{
                   DB::transaction(function() use($validateStudentCourse, $user, $request){
                       $termination=TerminationCourseRequests::create([
                           "student_course_id"=>$request->student_course_id,
                           "from"=>$request->from,
                           "created_at" => now(),
                           "updated_at" => now(),
                       ]);
                       $getCommonRequestId=$termination->request()->insertGetId([
                           "message"=>$request->message,
                           "status"=>"UNDER_REVIEW",
                           "requestable_type" => "App\Models\TerminationCourseRequests",
                           "requestable_id" => $termination->id,
                           "created_at" => now(),
                           "updated_at" => now()
                       ]);
                       Notifications::create([
                           "receiver_id"=>$validateStudentCourse->courseInfos->teacher_id,
                           "message"=>"messages.notification.terminationOfCourse",
                           "url"=>"/requests/".$getCommonRequestId,
                       ]);
                   });
                   return response()->json(["message"=>__("messages.success")],200);
               }catch (\Exception $e){
                   throw new ControllerException(__("messages.error"),500);
               }
           }
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        throw new ControllerException(__("messages.denied.permission"),403);
    }
    public function studentTerminatedByTeacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "studentCourseId"=>"required|numeric|exists:student_course,id",
            "termination_date"=>"required|date"
        ],[
            "studentCourseId.required"=>__("validation.custom.student_course.id.required"),
            "studentCourseId.numeric"=>__("validation.custom.student_course.id.numeric"),
            "studentCourseId.exists"=>__("validation.custom.student_course.id.exists"),
            "termination_date.required"=>__("validation.custom.termination.required"),
            "termination_date.date"=>__("validation.custom.termination.date"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getCourseInfos=StudentCourse::where('id',$request->studentCourseId)->with("parentInfo")->first();
        $getCommonRequest=CommonRequests::where(['requestable_type'=>"App\Models\TeacherCourseRequests", "requestable_id" =>$getCourseInfos->teacher_course_request_id])->first();
        if($getCourseInfos && $getCommonRequest){
            $validateDates=$getCourseInfos->start_date <= $request->termination_date && $getCourseInfos->end_date >= $request->termination_date;
            if(!$validateDates){
                $validatorResponse=[
                    "validatorResponse"=>[__("validation.custom.termination.invalid_interval")]
                ];
                return response()->json($validatorResponse,422);
            }
        }
        if(Permission::checkPermissionForTeachers('WRITE',$getCourseInfos->teacher_course_id,null)){
            try {
                DB::transaction(function() use($request, $user, &$getCourseInfos, &$getCommonRequest){
                    $getCommonRequest->update([
                        "status"=>"REJECTED"
                    ]);
                    $getCourseInfos->update([
                        "end_date" => $request->termination_date
                    ]);
                    foreach ($getCourseInfos->parentInfo as $parent) {
                        Notifications::create([
                            "receiver_id"=>$parent->id,
                            "message"=>"messages.notification.studentCourseTerminated",
                            "url"=>"/child/".$getCourseInfos->child_id ."/course/".$getCourseInfos->id,
                        ]);
                    }
                });
                return response()->json(["message"=>__("messages.success")]);
            }catch (\Exception $e){
                event(new ErrorEvent($user,'Hack Attempt', '500', __("messages.hack_attempt"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.hack_attempt"), 429);
            }
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        throw new ControllerException(__('messages.denied.role'),403);
    }

    public function acceptTerminationRequest(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:termination_course_requests,id",
            "termination_date"=>"required|date"
        ],[
            "termination_date.required"=>__("validation.custom.termination.required"),
            "termination_date.date"=>__("validation.custom.termination.date"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $user=JWTAuth::parseToken()->authenticate();

        $getTerminationRequest=TerminationCourseRequests::where('id',$request->requestId)->first();
        $getTerminationFromCommonRequest=CommonRequests::where(["requestable_type" => "App\Models\TerminationCourseRequests", "requestable_id" => $request->requestId])->first();
        $getCourseInfos=StudentCourse::where('id', $getTerminationRequest->student_course_id)->with("parentInfo")->first();
        if($getCourseInfos && $getTerminationRequest){
            $validateDates=$getCourseInfos->start_date <= $request->termination_date && $getCourseInfos->end_date >= $request->termination_date;
            if(!$validateDates){
                $validatorResponse=[
                    "validatorResponse"=>[__("validation.custom.termination.invalid_interval")]
                ];
                return response()->json($validatorResponse,422);
            }
        }
        if(Permission::checkPermissionForTeachers('WRITE',$getCourseInfos->teacher_course_id,null)){
            try {
                DB::transaction(function() use($request, $getTerminationRequest, $user, $getCourseInfos, &$getTerminationFromCommonRequest){
                    $getTerminationFromCommonRequest->update([
                        "status"=>"ACCEPTED",
                    ]);
                    $getCourseInfos->update([
                        "end_date" => $request->termination_date
                    ]);
                    foreach ($getCourseInfos->parentInfo as $parent) {
                        Notifications::create([
                            "receiver_id"=>$parent->id,
                            "message"=>"messages.notification.terminationAccepted",
                            "url"=>"/requests/".$getTerminationFromCommonRequest->id,
                        ]);
                    }
                });
                return response()->json(["message"=>__("messages.success")]);
            }catch (\Exception $e){
                event(new ErrorEvent($user,'Hack Attempt', '500', __("messages.hack_attempt"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.hack_attempt"), 429);
            }
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        throw new ControllerException(__('messages.denied.role'),403);
    }
    public function rejectTerminationRequest(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:termination_course_requests,id",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getTerminationRequest=TerminationCourseRequests::where('id',$request->requestId)->first();
        $getTerminationFromCommonRequest=CommonRequests::where(["requestable_type" => "App\Models\TerminationCourseRequests", "requestable_id" => $request->requestId])->first();
        $getCourseInfos=StudentCourse::where('id', $getTerminationRequest->student_course_id)->with("parentInfo")->first();

        if(Permission::checkPermissionForTeachers('WRITE',$getCourseInfos->teacher_course_id,null)){
            try {
                DB::transaction(function() use($request, $getTerminationRequest, $user, $getCourseInfos, &$getTerminationFromCommonRequest){
                    $getTerminationFromCommonRequest->update([
                        "status"=>"REJECTED",
                    ]);
                    foreach ($getCourseInfos->parentInfo as $parent) {
                        Notifications::create([
                            "receiver_id"=>$parent->id,
                            "message"=>"messages.notification.rejected",
                            "url"=>"/requests/".$getTerminationFromCommonRequest->id,
                        ]);
                    }
                });
            }catch (\Exception $e){
                event(new ErrorEvent($user,'Hack Attempt', '500', __("messages.hack_attempt"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.hack_attempt"), 429);
            }

            return response()->json(__('messages.success'));

        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.role'),403);
    }

}
