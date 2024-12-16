<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\CourseInfos;
use App\Models\CourseLocations;
use App\Models\Locations;
use App\Models\TeacherLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LocationController extends Controller
{
    public function index()
    {

    }
    public function create(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        $validation = Validator::make($request->all(),[
            "name"=>"required|max:255",
            "country"=>"required|max:255",
            "zip"=>"required|max:255",
            "city"=>"required|max:255",
            "street"=>"required|max:255",
            "number"=>"required|max:255",
            "floor"=>"nullable|max:255",
            "door"=>"nullable|max:255",
            "locationId"=>"nullable|exists:locations,id",
            "selectedCourseId"=>"nullable|exists:course_infos,id",
        ],[
            "name"=>__("validation.custom.name.required"),
            "name.max"=>__("validation.custom.name.max"),
            "country.required" => __("validation.custom.country.required"),
            "country.max" => __("validation.custom.country.max"),
            "zip.required" => __("validation.custom.zip.required"),
            "zip.max" => __("validation.custom.zip.max"),
            "city.required" => __("validation.custom.city.required"),
            "city.max" => __("validation.custom.city.max"),
            "street.required" => __("validation.custom.street.required"),
            "street.max" => __("validation.custom.street.max"),
            "number.required" => __("validation.custom.number.required"),
            "number.max" => __("validation.custom.number.max"),
            "floor.nullable" => __("validation.custom.floor.nullable"),
            "floor.max" => __("validation.custom.floor.max"),
            "door.nullable" => __("validation.custom.door.nullable"),
            "door.max" => __("validation.custom.door.max"),
            "locationId.nullable" => __("validation.custom.locationId.nullable"),
            "locationId.exists" => __("validation.custom.locationId.exists"),
            "selectedCourseId.nullable" => __("validation.custom.selectedCourseId.nullable"),
            "selectedCourseId.exists" => __("validation.custom.selectedCourseId.exists"),

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
                $alreadyAttachedToCourse = CourseLocations::where(["location_id"=>$checkLocation->id, "course_id"=>$request->selectedCourseId])->exists();

                if ($alreadyAttachedToCourse) {
                    throw new ControllerException(__("messages.attached.location"));
                }
            }

            if (empty($checkLocation)) {
                try {
                    DB::transaction(function () use (&$request, $user) {
                        $newLocation = Locations::insertGetId([
                            "name" => $request->name,
                            "country" => $request->country,
                            "city" => $request->city,
                            "zip" => $request->zip,
                            "street" => $request->street,
                            "number" => $request->number,
                            "floor" => $request->floor,
                            "door" => $request->door
                        ]);
                        $courseExist = CourseLocations::where(["course_id"=>$request->selectedCourseId])->first();
                        if($courseExist){
                            $courseExist->update(["course_id"=>$request->selectedCourseId, "location_id"=>$newLocation]);
                        }else{
                            CourseLocations::create(["course_id"=>$request->selectedCourseId, "location_id"=>$newLocation]);
                        }
                        TeacherLocation::create([
                            "teacher_id" => $user->id,
                            "location_id" => $newLocation
                        ]);
                    });
                } catch (\Exception $e) {
                    event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                    throw $e;
                }
            }
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
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.error"));
            }
        }
        return response()->json(__("messages.success"), 200);
    }
    public function getCourseLocations(Request $request){
        $validator=$request->validate([
            "courseId"=>"required|exists:course_infos,id"
        ]);
        $header=[
            "id",
            "name",
            "city",
            "zip",
            "street",
            "number",
            "floor",
            "door",
        ];
        $checkLocations=CourseLocations::where("course_id", $request->courseId)->exists();
        if(!$checkLocations){
            $notFound=[
                "message"=>__("messages.notFound.location"),
                "header"=>$header
            ];
            return response()->json($notFound, 200);
        }
        $getSchoolLocations= CourseInfos::with('location')->find($request->courseId);

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
    public function getLocations(Request $request){
        if(Permission::checkPermissionForTeachers("READ", null, null)){
            $user=JWTAuth::parseToken()->authenticate();

            $getCourseIds=CourseInfos::where('teacher_id', $user->id)->pluck('id');
            $getLocationsIds=CourseLocations::whereIn('course_id', $getCourseIds)->pluck('location_id');

            $getAllCourseLocation= Locations::whereIn('id', $getLocationsIds)->get();
            $selectData=[];
            $header=[
                "id","name","country","city","zip","street","number","floor","door"
            ];
            foreach ($getAllCourseLocation as $location){
                $selectData[]=[
                    "value"=>$location->id,
                    "label"=>$location->name
                ];
            }
            return response()->json([
                "header"=>$header,
                "data"=>$getAllCourseLocation,
                "select"=>$selectData
            ], 200);
        }
    }
    public function getLocationInfo($locationId){
        $validation=Validator::make(["locationId"=>$locationId],[
            "locationId"=>"required|numeric|exists:locations,id"
        ],[
            "locationId.required"=>__("validation.custom.locationId.required"),
            "locationId.numeric"=>__("validation.custom.locationId.numeric"),
            "locationId.exists"=>__("validation.custom.locationId.exists")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(Permission::checkPermissionForTeachers("WRITE",null, $locationId)){

            $getLocationData= Locations::where("id", $locationId)->firstOrFail();

            return response()->json($getLocationData);

        }else{
            throw new ControllerException('messages.permission');
        }
    }
    public function removeCourseLocation(Request $request){
        $validator=$request->validate([
            "courseId"=>"required|exists:course_infos,id",
            "locationId"=>"required|exists:locations,id"
        ]);
        $validateSchoolLocation=CourseLocations::where(["location_id"=> $request->locationId, "course_id"=>$request->courseId])->first();
        if(!empty($validateSchoolLocation)){
            $validateSchoolLocation->delete();
            return response()->json(__("messages.detached.location"));
        }else{
            $user=JWTAuth::parseToken()->authenticate();
            event(new ErrorEvent($user,'Remove', '500', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.error"));
        }
    }
}
