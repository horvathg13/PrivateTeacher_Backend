<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\CourseInfos;
use App\Models\CourseLangsNames;
use App\Models\Labels;
use App\Models\Roles;
use App\Models\Schools;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SearchController extends Controller
{
    public function index()
    {

    }
    public function searchLabel(Request $request){
        Validator::validate($request->all(),[
            "keyword"=>"nullable"
        ]);
        $label = $request->keyword;
        $findLabel = Labels::where('label','ILIKE', "%$label%")->where("lang", $request->header('locale'))->get();
        $success=[];

        if($findLabel->isNotEmpty()){
            foreach($findLabel as $label){
                $success[]=[
                    "id"=>$label->id,
                    "label"=>$label->label
                ];
            }
            return response()->json($success);
        }else{
            throw new ControllerException(__("messages.notFound.search"));
        }

    }

    public function createLabel(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForTeachers("READ",null, null)){
            $validator = Validator::make($request->all(), [
                "keyword"=>"required|unique:labels,label",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            $findLabel = Labels::where(['label'=> $request->keyword, 'lang'=>$request->header('Locale')])->exists();

            if($findLabel === false){
                DB::transaction(function() use($request){
                    Labels::create([
                        "label"=>$request->keyword,
                        "lang"=>$request->header('Locale')
                    ]);
                });
                return response()->json(__("messages.success"));
            }else{
                event(new ErrorEvent($user,'Create', '404', __("messages.error"), json_encode(debug_backtrace())));
                return response()->json(__("messages.error"),404);
            }
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.denied.permission"), 403);
        }
    }

    public function searchTeacher(Request $request){

        $email=$request->email;

        if($email === null){
            throw new ControllerException("Invalid search credentials");
        }

        $findUser = User::where('email', "ILIKE", "%$email%")->first();
        $getCourses=[];
        if($findUser){
            $getUserRoles=$findUser->roles()->get();
            $isTeacher=$getUserRoles->some(function ($k){
                return $k->name === 'Teacher';
            });
            $finalData=[];
            if($isTeacher){
                $getCourses=$findUser->with('courses')->get();
                foreach ($getCourses as $c){
                    if($c->courses->count()) {
                        $courseNameCollection = [];
                        foreach ($c->courses as $key=>$value) {
                            if ($c->courses[$key]->course_id === $c->courses[$key + 1]->course_id) {
                                $courseNameCollection[] = $value->name;
                                $finalData[] = [
                                    "id" => $c->courses[0]->course_id,
                                    "teacher_name" => $c->first_name . ' ' . $c->last_name,
                                    "email" => $c->email,
                                    "course_name" => $courseNameCollection,
                                ];
                            }else {
                                foreach ($c->courses as $courseName) {

                                    $finalData[] = [
                                        "id" => $courseName->course_id,
                                        "teacher_name" => $c->first_name . ' ' . $c->last_name,
                                        "email" => $c->email,
                                        "course_name" => $courseName->name,
                                    ];
                                }
                            }
                        }
                    }
                }
            }else{
                throw new ControllerException(__('auth.invalid.email'));
            }

            $header=[__("tableHeaders.id")=>false, __("tableHeaders.teacher_name")=>false, __("tableHeaders.email")=>false, __("tableHeaders.course_name")=>false];
            $success=[
                "header"=>$header,
                "data"=>$finalData
            ];
            return response()->json($success,200);
        }else{
            return response()->json([__('messages.notFound.user')],404);
        }


    }

    public function searchSchool(Request $request){

        $name = $request->name?: null;
        $country = $request->country?: null;
        $zip=$request->zip?: null;
        $city=$request->city?: null;
        $street=$request->street?: null;
        $number=$request->number?: null;

        $getSchools= Schools::query();

        if($name!== null){
            $getSchools->where("name","LIKE","%$request->name%");
        }
        if($country!== null){
            $getSchools->where("country","LIKE","%$request->country%");
        }
        if($zip!== null){
            $getSchools->where("zip","LIKE","%$request->zip%");
        }
        if($city!== null){
            $getSchools->where("city","LIKE", "%$request->city%");
        }
        if($street!== null){
            $getSchools->where("street","LIKE", "%$request->street%");
        }
        if($number!== null){
            $getSchools->where("number","LIKE", "%$request->number%");
        }

        if(!empty($request->sortData)){
            foreach($request->sortData as $sort){
                $getSchools->orderBy($sort['key'], $sort['abridgement']);
            }
        }

        $Results=$getSchools->paginate($request->perPage ?: 5);

        $paginator=[
            "currentPageNumber"=>$Results->currentPage(),
            "hasMorePages"=>$Results->hasMorePages(),
            "lastPageNumber"=>$Results->lastPage(),
            "total"=>$Results->total(),
        ];

        if($Results){
            foreach($Results as $school){
                $datas[]= [
                    "id"=>$school['id'],
                    "name"=>$school['name'],
                    "country"=>$school['country'],
                    "zip"=>$school['zip'],
                    "city"=>$school['city'],
                    "street"=>$school['street'],
                    "number"=>$school['number']
                ];
            }
        }
        if(empty($datas)){
            $datas[]=["There are no results"];
        }

        $header=["id"=>false, "name"=>false,"country"=>false,"zip"=>false,"city"=>false,"street"=>false,"number"=>false];
        $success=[
            "header"=>$header,
            "data"=>$datas,
            "pagination"=>$paginator
        ];

        return response()->json($success,200);
    }

    public function searchCourse(Request $request){

        $validator = Validator::make($request->all(), [
            "name"=>"max:255",
            "country"=>"max:255",
            "zip"=>"max:255",
            "city"=>"max:255",
            "street"=>"max:255",
            "number"=>"max:255",


        ],[
            "name.max"=>__("validation.custom.courseName.max"),
            "country.max" => __("validation.custom.country.max"),
            "zip.max" => __("validation.custom.zip.max"),
            "city.max" => __("validation.custom.city.max"),
            "street.max" => __("validation.custom.street.max"),
            "number.max" => __("validation.custom.number.max"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        //var checks
        $keywords =$request->keywords?:null;
        $country = $request->country?: null;
        $zip=$request->zip?: null;
        $city=$request->city?: null;
        $street=$request->street?: null;
        $number=$request->number?: null;
        $courseName=$request->name?:null;
        $min_lesson=$request->min_lesson?:null;
        $minimum_t_days=$request->min_t_days?:null;
        $course_price=$request->course_price?:null;
        $teacherEmail=$request->teacher_email?:null;

        //query build
        $courseInfosQuery=CourseInfos::query();

        //predefine var
        $data=[];
        $findCourses=[];

        //getSchools

        $courseInfosQuery->where('course_status', 'ACTIVE');

        if($country !== null){
            $courseInfosQuery->whereRelation("location",'country', "ILIKE", "%$country%");
        }
        if($zip !== null){
            $courseInfosQuery->whereRelation("location",'zip', "ILIKE", "%$zip%");
        }
        if($city !== null){
            $courseInfosQuery->whereRelation("location",'city', "ILIKE", "%$city%");
        }
        if($street !== null){
            $courseInfosQuery->whereRelation("location",'street', "ILIKE", "%$street%");
        }
        if($number !== null){
            $courseInfosQuery->whereRelation("location",'number', "ILIKE", "%$number%");
        }

        //getTeacher

        if($teacherEmail !== null){
            $courseInfosQuery->whereRelation("teacher",'email', "ILIKE", "%$teacherEmail%");
        }

        //keyword Check
        if($keywords !== null){

            $courseInfosQuery->whereRelation('label',fn($q) => $q->whereIn('id',array_column($keywords, 'id')));

        }

        if(!empty($findCourses)){
            $courseInfosQuery->whereIn('id', $findCourses);
        }



        if($courseName !==null){
            $courseInfosQuery->where("name", $courseName);
        }
        if($min_lesson !==null){
            $courseInfosQuery->where("minutes_lesson", $min_lesson);
        }
        if($minimum_t_days!==null){
            $courseInfosQuery->where("min_teaching_day", $minimum_t_days);
        }
        if($course_price!==null){
            $courseInfosQuery->where("course_price_per_lesson", $course_price);
        }

        if(!empty($request->sortData)){
            foreach($request->sortData as $sort){
                $courseInfosQuery->orderBy($sort['key'], $sort['abridgement']);
            }
        }

        $result=$courseInfosQuery->paginate($request->perPage ?:10);

        $paginator=[
            "currentPageNumber"=>$result->currentPage(),
            "hasMorePages"=>$result->hasMorePages(),
            "lastPageNumber"=>$result->lastPage(),
            "total"=>$result->total(),
        ];

        if($result){
            foreach($result as $r){
                $getCourseNamesLangs=CourseLangsNames::where('course_id',$r['id'])->get();
                $languages=[];
                foreach ($getCourseNamesLangs as $n) {
                    $languages[] = $n['lang'];
                }
                $commaSeparatedLanguages=implode(', ',$languages);
                $getCourseName= CourseLangsNames::where('course_id', $r['id'])->first();
                $getTeacher=User::where('id',$r['teacher_id'])->first();
                $data[] = [
                    "id" => $r['id'],
                    "name" => $getCourseName->name,
                    "Lang"=>$commaSeparatedLanguages,
                   /* "student_limit" => $r['student_limit'],
                    "minutes_lesson" => $r['minutes_lesson'],*/
                    "course_price_per_lesson" => $r['course_price_per_lesson'],
                    "teacher_name"=>$getTeacher->first_name . ' '. $getTeacher->last_name
                ];
            }
        }
        $header=[
            "id"=>false,
            "name"=>false,
            "language"=>false,
            "course_price_per_lesson"=>false,
            "teacher_name"=>false
        ];
        $success=[
            "header"=>$header,
            "data"=>$data,
            "pagination"=>$paginator
        ];

        return response()->json($success,200);
    }
}
