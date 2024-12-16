<?php

namespace App\Http\Controllers;

use App\Exceptions\ControllerException;
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
use App\Models\TeachingDays;
use App\Models\User;
use App\Models\UserRoles;
use Carbon\Carbon;
use Carbon\CarbonInterval;
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
            throw new ControllerException(__("messages.notFound.school"));
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
            throw new ControllerException(__("messages.denied.role"));
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
            throw new ControllerException(__("messages.denied.role"));
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
            throw new ControllerException(__("messages.denied.role"));
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
                throw new ControllerException(__("messages.attached.exists"));
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
                throw new ControllerException(__("messages.attached.exists"));
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
                throw new ControllerException(__("messages.notFound.user"));
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
        }else{
            throw new ControllerException(__("messages.denied.role"));
        }
    }

    function getTimeSlot($interval, $start_time, $end_time){

        $start = new \DateTime($start_time);
        $end = new \DateTime($end_time);
        $startTime = $start->format('H:i');
        $endTime = $end->format('H:i');
        $i=0;
        $time = [];
        while(strtotime($startTime) <= strtotime($endTime)) {
            $start = $startTime;
            $end = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
            $startTime = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
            $i++;
            if (strtotime($startTime) <= strtotime($endTime)) {
                $time[]=[
                    "start"=>$start,
                    "end"=>$end
                ];
            }
        }


        return $time;
    }
    public function createTeachingDay(Request $request){
        $validate=Validator::make($request->all(),[
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required",
            "courseId"=>"required|exists:course_infos,id",
            "days"=>"required",
            "teacherId"=>"required|exists:users,id",
            "startTime"=>"required",
            "endTime"=>"required|after:startTime",
            'locationId'=>'required',
            'teachingDayId'=>'nullable'
        ]);
        if($validate->fails()){
            return response()->json($validate->errors());
        }
        if(Permission::checkPermissionForSchoolService("WRITE", $request->schoolId)) {
            if(!$request->teachingDayId) {

                $courseInfo = CourseInfos::where('id', $request->courseId)->first();
                $courseLength = intval($courseInfo->minutes_lesson);
                $generatedTime = $this->getTimeSlot($courseLength, $request->startTime, $request->endTime);
                $getSchoolLocationId = SchoolLocations::where(['school_id' => $request->schoolId, 'location_id' => $request->locationId])->first();
                $successTimeTable = [];
                foreach ($request->days as $day) {
                    foreach ($generatedTime as $time) {
                        $successTimeTable[] = [
                            "day" => $day,
                            "teacher_id" => $request->teacherId,
                            "course_id" => $request->courseId,
                            "school_location_id" => $getSchoolLocationId->id,
                            "start" => $time['start'],
                            "end" => $time['end'],
                        ];
                    }
                }
                DB::transaction(function () use($successTimeTable){
                    TeachingDays::insert($successTimeTable);
                });

                return response()->json(__("messages.success"));
            }else{
                $findTeachingDay= TeachingDays::where('id', $request->teachingDayId)->first();
                $getSchoolLocationId = SchoolLocations::where(['school_id' => $request->schoolId, 'location_id' => $request->locationId])->first();
                DB::transaction(function () use($findTeachingDay, $getSchoolLocationId, $request){
                    $findTeachingDay->update([
                        "day"=>$request->days,
                        "teacher_id" => $request->teacherId,
                        "course_id" => $request->courseId,
                        "start"=>$request->startTime,
                        "end" => $request->endTime,
                        "school_location_id" => $getSchoolLocationId->id
                    ]);
                });
                return response()->json(__("messages.success"));
            }
        }else{
            throw new ControllerException(__("messages.denied.role"));
        }
    }
    public function getTeachingDays(Request $request){
        $validate=Validator::make($request->all(),[
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "courseId"=>"nullable|exists:course_infos,id",
            "teacherId"=>"nullable|exists:course_infos,teacher_id"
        ]);
        if($validate->fails()){
            return response()->json($validate->errors());
        }

        $validateSchoolYear=SchoolYears::where('id',$request->yearId)->first();
        if($validateSchoolYear['year_status'] !== 'ACTIVE'){
            throw new ControllerException(__("messages.invalid.year"));
        }
        if(!$request->courseId && !$request->teacherId){
            throw new ControllerException(__('messages.error'));
        }

        $getSchoolLocationId=SchoolLocations::where('school_id', $request->schoolId)->first();
        $courseInfosQuery=CourseInfos::where(['school_year_id'=>$request->yearId, 'school_location_id' => $getSchoolLocationId['id']]);

        if($request->courseId){
            $courseInfosQuery->where([ 'id'=>$request->courseId]);
        }
        if($request->teacherId){
            $courseInfosQuery->where([ 'teacher_id' => $request->teacherId]);
        }

        $getData = $courseInfosQuery->with('courseNamesAndLangs')->with('teacher')->first();

        $tableData=[];
        $get_Day=[];

       // $tableData = TeachingDays::where('course_id', $getData['id'])->get()->groupBy('day');
        $tableData = TeachingDays::where('course_id', $getData['id'])->get();
        $event=[];
        $min=null;
        $max=null;
        /*foreach ($tableData as $day) {
            $dayToDate=Carbon::parse("$day->day", "UTC");
            $dayStart=Carbon::createFromFormat("H:i:s", $day->start);
            $dayEnd=Carbon::createFromFormat("H:i:s", $day->end);
            $event[]=[
                "title"=>"Zongoraóra",
                "start"=>$dayToDate->toDateString().'T'.$dayStart->format("H:i:s"),
                "end"=>$dayToDate->toDateString().'T'.$dayEnd->format("H:i:s"),
            ];

        }*/

        $min=$tableData->min('start');
        $max=$tableData->max('end');

        $duration= CarbonInterval::minutes($getData->minutes_lesson);
        $format=sprintf("%02d:%02d:%02d", $duration->h, $duration->i, $duration->s);
        /*$format=Carbon::parse($getData->minutes_lesson)->minutes($getData->minutes_lesson)->format("H:i:s");*/

        $success=[
            "data"=>$tableData,
            "min"=>$min,
            "max"=>$max,
            "duration"=>$format
        ];
        return response()->json($success);
    }
}
