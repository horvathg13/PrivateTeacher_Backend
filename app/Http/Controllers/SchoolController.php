<?php

namespace App\Http\Controllers;

use App\Helper\Permission;
use App\Models\CourseInfos;
use App\Models\CourseLabels;
use App\Models\Locations;
use App\Models\Roles;
use App\Models\SchoolBreaks;
use App\Models\SchoolLocations;
use App\Models\Schools;
use App\Models\SchoolYears;
use App\Models\SpecialWorkDays;
use App\Models\Statuses;
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
        }catch (Exception $e){
            throw $e;
        }

        return response()->json(["message"=>"School Creation Success"],200);
    }


    public function SchoolList(Request $request){
        $list = Schools::paginate($request->perPage ?: 5);


        if(!$list){
            throw new Exception('Schools not found');
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
        if($schoolId){
            $school=Schools::where('id', $schoolId)->first();

            return response()->json($school);
        }else{
            throw new Exception('Request fail');
        }
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
                return response()->json(["message"=>"Update Successful"]);

            }
        }else{
            throw new Exception("Denied");
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

    public function getSchoolYearStatuses(Request $request){
        $get=Statuses::whereIn("status", ['Active', 'Closed'])->get();

        if(!empty($get)){
            return response()->json($get);
        }else{
            throw new Exception('Something went wrong');
        }

    }

    public function createSchoolYear(Request $request){
        if(Permission::checkPermissionForSchoolService("WRITE",$request->schoolId)){
            $validator = Validator::make($request->all(), [
                "schoolYear"=>"required",
                "name"=>"required",
                "startDate"=>"required",
                "endDate"=>"required",
                "id"=>"nullable",
                "statusId"=>"required"
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            if($request->endDate < $request->startDate){
                throw new Exception("The end of the school year must be later then start date!");
            }
            if($request->id != null){
                $findSchoolYear= SchoolYears::where("id", $request->id)->first();

                DB::transaction(function () use ($request, $findSchoolYear){

                    $findSchoolYear->update([
                        "year"=>$request->schoolYear,
                        "name"=>$request->name,
                        "start" => $request->startDate,
                        "end" => $request->endDate,
                        "year_status"=>$request->statusId,
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
                        "year_status"=>"ACTIVE",
                    ]);
                });
            }
            return response()->json(["Opration Successful"],200);
        }else{
            throw new Exception("Denied");
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


            return response()->json("Operation Successful");
        }else{
            throw new Exception("Denied");
        }

    }
    public function getSchoolYearInfos($schoolId, $schoolYearId){
        $validSchool = Schools::where("id", $schoolId)->exists();
        $validSchoolYear = SchoolYears::where("id", $schoolYearId)->exists();

        if($validSchool === false || $validSchoolYear === false){
            throw new Exception("Invalid server call");
        }


        $SchoolYearDetails = SchoolYears::where("id", $schoolYearId)->first();
        $schoolInfos = Schools::where("id", $schoolId)->first();
        //$status=Statuses::where('id',$SchoolYearDetails->year_status)->first();

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


        /*$tableData=[];
        foreach($years as $year){
            $tableData[]=[
                "id"=>$year->id,
                "year"=>$year->year,
                "name"=>$year->name,
                "start"=>$year->start,
                "end"=>$year->end,
            ];
        }*/

        // getSpecialWorkDays

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
        // role base check...
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
            }catch(Exception $e){
                throw $e;
            }
            return response("Create Successful");
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
                }catch(Exception $e){
                    throw $e;
                }

                return response("Update Successful!");
            }
        }
    }
    public function createSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
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
            }catch(Exception $e){
                throw $e;
            }
            return response("Create Successful");
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
                }catch(Exception $e){
                    throw $e;
                }

                return response("Update Successful!");
            }
        }

    }

    public function removeSchoolBreak(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
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
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");

    }

    public function removeSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
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
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");

    }

    public function createSchoolCourse(Request $request){
        //Role base check here...
        $user= JWTAuth::parsetoken()->authenticate();

        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "courseId"=>"nullable|exists:course_infos,id",
            "name"=>"required",
            "subject"=>"required",
            "studentLimit"=>"required",
            "minutesLesson"=>"required",
            "minTeachingDay"=>"required",
            "doubleTime"=>"nullable",
            "couresPricePerLesson"=>"required",
            "status"=>"required",
            "labels"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        if($request->courseId === null){

            try{
                $uniqueControl= CourseInfos::where([
                    "school_id"=>$request->schoolId,
                    "school_year_id"=>$request->yearId,
                    "name"=>$request->name
                ])->exists();
                if($uniqueControl === false){
                    DB::transaction(function() use($request){
                        CourseInfos::create([
                            "name"=>$request->name,
                            "subject"=>$request->subject,
                            "student_limit"=>$request->studentLimit,
                            "minutes_lesson"=>$request->minutesLesson,
                            "min_teaching_day"=>$request->minTeachingDay,
                            "double_time"=>$request->doubleTime ?: false,
                            "course_price_per_lesson"=>$request->couresPricePerLesson,
                            "status_id"=>$request->status,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
                }else{
                    throw new Exception('The course must be unique until a school year');
                }
            }catch(Exception $e){
                throw $e;
            }
            try{
                $findCourse = CourseInfos::where([
                    "school_id"=>$request->schoolId,
                    "school_year_id"=>$request->yearId,
                    "name"=>$request->name
                ])->first();

                if($findCourse){
                    DB::transaction(function () use ($request, $findCourse){

                        foreach($request->labels as $label){
                            CourseLabels::insert(["course_id"=>$findCourse['id'], "label_id"=>$label['id']]);
                        }
                    });
                }
            }catch (Exception $e){
                throw $e;
            }
            return response("Create Successful");
        }else{
            $findCourse=CourseInfos::where("id", $request->courseId)->first();
            if($findCourse){
                try{

                    DB::transaction(function() use($request, $findCourse){
                        $findCourse->update([
                            "name"=>$request->name,
                            "subject"=>$request->subject,
                            "student_limit"=>$request->studentLimit,
                            "minutes/lesson"=>$request->minutesLesson,
                            "min_teaching_day"=>$request->minTeachingDay,
                            "double_time"=>$request->doubleTime,
                            "course_price_per_lesson"=>$request->couresPricePerLesson,
                            "status_id"=>$request->status,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });

                }catch(Exception $e){
                    throw $e;
                }
                try{
                    DB::transaction(function () use ($request){
                        foreach($request->labels as $label){
                            CourseLabels::insert(["course_id"=>$request->courseId, "label_id"=>$label['id']]);
                        }
                    });
                }catch (Exception $e){
                    throw $e;
                }
                return response("Update Successful");
            }else{
                throw new Exception("Database error occured!");
            }

        }
    }

    public function getSchoolCourses($schoolId, $schoolYearId){

        $user= JWTAuth::parsetoken()->authenticate();

        $courses=CourseInfos::where(["school_id"=>$schoolId, "school_year_id"=>$schoolYearId])->get();

        $tableHeader=[
            "id",
            'name',
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
                $status = $course->status()->first();
                $final[]=[
                    "id"=>$course->id,
                    'name'=>$course->name,
                    'subject'=>$course->subject,
                    'student_limit'=>$course->student_limit,
                    'minutes_lesson'=>$course->minutes_lesson,
                    'min_teaching_day'=>$course->min_teaching_day,
                    'double_time'=>$course->double_time,
                    'course_price_per_lesson'=>$course->course_price_per_lesson,
                    'status'=>$status->status,
                ];
            }

            $success=[
                "header"=>$tableHeader,
                "courses"=>$final
            ];
            return response()->json($success,200);
        }else{
            throw new Exception("Database error occured");
        }

    }

    public function removeSchoolCourse(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
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
        return response("Success");
    }

    public function getSchoolCourseStatuses(){

        $CourseStatuses=Statuses::whereIn("status", ["Active", "Suspended"])->get();

        if($CourseStatuses){
            $success=[];
            foreach($CourseStatuses as $status){
                $success[]=[
                    "id"=>$status->id,
                    "label"=>$status->status
                ];
            }
            return response()->json($success);
        }else{
            throw new Exception("Error Orrured!");
        }
    }

    public function getSchoolCourseInfo($schoolId, $schoolYearId, $courseId){
        $user= JWTAuth::parsetoken()->authenticate();

        if($schoolId === null || $schoolYearId === null || $courseId === null){
            throw new Exception("Request fail");
        }else{
            $course=CourseInfos::where(["school_id"=>$schoolId, "school_year_id"=>$schoolYearId, "id"=>$courseId])->first();

            if($course){
                $labels= $course->label()->get();
                $status = $course->status()->first();
                $success=[
                    "courses"=>[
                        "id"=>$course->id,
                        'name'=>$course->name,
                        'subject'=>$course->subject,
                        'student_limit'=>$course->student_limit,
                        'minutes_lesson'=>$course->minutes_lesson,
                        'min_teaching_day'=>$course->min_teaching_day,
                        'double_time'=>$course->double_time,
                        'course_price_per_lesson'=>$course->course_price_per_lesson,
                        'status'=>$status->status,
                        'status_id'=>$course->status_id,
                        'labels'=>$labels
                    ]
                ];
                return response()->json($success,200);
            }else{
                throw new Exception("Database error occured");
            }
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
                    "id"=>$result["id"],
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
                    "id"=>$s['id'],
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
                    throw new \Exception("This Location Already attached this school");
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
            return response()->json("success", 200);
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
                throw new \Exception("The given data was invalid");
            }
            return response()->json("Update Success",200);
        }
    }
    public function getSchoolLocations(Request $request){
        $validator=$request->validate([
            "schoolId"=>"required|exists:school_locations,school_id"
        ]);
        $getSchoolLocations= Schools::with('location')->find($request->schoolId);
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
        $data=[];
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
        }
        $success=[
            "header"=>$header,
            "data"=>$data
        ];

        return response()->json($success);

    }
}
