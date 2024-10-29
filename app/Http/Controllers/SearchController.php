<?php

namespace App\Http\Controllers;

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

class SearchController extends Controller
{
    public function index()
    {

    }
    public function searchLabel(Request $request){
        Validator::validate($request->all(),[
            "keyword"=>"required"
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
            throw new \Exception(__("messages.notFound.search"));
        }

    }

    public function createLabel(Request $request){

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
        }
    }

    public function searchTeacher(Request $request){

        $email=$request->email;

        if($email === null){
            throw new Exception("Invalid search credentials");
        }

        $findUser = User::where('email', $email)->first();

        if($findUser){
            $teacher=Roles::where('name', "Teacher")->pluck('id')->first();
            $isTeacher= UserRoles::where(['user_id'=>$findUser['id'], 'role_id'=> $teacher])->exists();

            if($isTeacher){
                $getSchools=UserRoles::where(['user_id'=>$findUser['id'], 'role_id'=> $teacher])->pluck('reference_id');

                if($getSchools){
                    $findSchools=Schools::whereIn("id", $getSchools)->get();
                }
            }else{
                throw new Exception('Invalid email');
            }
        }

        $header=["id"=>false, "name"=>false,"country"=>false,"zip"=>false,"city"=>false,"street"=>false,"number"=>false];
        $success=[
            "header"=>$header,
            "data"=>$findSchools
        ];

        return response()->json($success,200);
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

        //var checks
        $keywords =$request->keywords?:null;
        $country = $request->country?: null;
        $zip=$request->zip?: null;
        $city=$request->city?: null;
        $street=$request->street?: null;
        $number=$request->number?: null;
        $courseName=$request->courseName?:null;
        $min_lesson=$request->min_lesson?:null;
        $minimum_t_days=$request->min_t_days?:null;
        $course_price=$request->course_price?:null;

        //query build
        $courseInfosQuery=CourseInfos::query();

        //predefine var
        $data=[];
        $findCourses=[];

        //getSchools

        $courseInfosQuery->where('course_status', 'ACTIVE');

        if($country !== null){
            $courseInfosQuery->whereRelation("school",'country', "ILIKE", "%$country%");
        }
        if($zip !== null){
            $courseInfosQuery->whereRelation("school",'zip', "ILIKE", "%$zip%");
        }
        if($city !== null){
            $courseInfosQuery->whereRelation("school",'city', "ILIKE", "%$city%");
        }
        if($street !== null){
            $courseInfosQuery->whereRelation("school",'street', "ILIKE", "%$street%");
        }
        if($number !== null){
            $courseInfosQuery->whereRelation("school",'number', "ILIKE", "%$number%");
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

        $result=$courseInfosQuery->paginate($request->perPage ?: 5);

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
            "languages"=>false,
            /*"student_limit"=>false,
            "minutes_lesson"=>false,*/
            "course_price_per_lesson"=>false,
            "teacher"=>false,
        ];
        $success=[
            "header"=>$header,
            "data"=>$data,
            "pagination"=>$paginator
        ];

        return response()->json($success,200);
    }
    public function searchSchoolTeacher(Request $request)
    {
        $schoolId = $request->schoolId;
        $keyword = $request->keyword;
        $courseId = $request->courseId;

        if($schoolId === null || $keyword === null || $courseId === null){
            throw new Exception("Invalid search");
        }

    }
}
