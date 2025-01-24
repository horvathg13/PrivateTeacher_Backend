<?php

namespace App\Helper;

use App\Exceptions\ControllerException;
use App\Models\CourseInfos;
use App\Models\StudentCourse;
use Carbon\Carbon;

class Student
{
    public static function checkLimit($courseId,$date_from,$date_to=null): array
    {
        $getCourseInfo=CourseInfos::where('id', $courseId)->first();
        if($date_to === null){
            $date_to=$getCourseInfo->end_date;
        }
        if(!$getCourseInfo){
            throw new ControllerException(__("messages.notFound.course"),404);
        }
        $getStudentCourses=StudentCourse::where("teacher_course_id", $courseId)
            ->orderBy('start_date', "asc")
        ->get();

        $borderDays=[];

        foreach ($getStudentCourses as $course){
            $borderDays[]=$course->start_date;
            $borderDays[]=Carbon::create($course->start_date)->subDay()->format("Y-m-d");
            $borderDays[]=$course->end_date;
            $borderDays[]=Carbon::create($course->end_date)->addDay()->format("Y-m-d");
        }
        $borderDays=array_filter($borderDays, function ($borderDay) use($date_from, $date_to){
            return $date_from <= $borderDay && $date_to >= $borderDay;
        });

        $finalBorderDays=array_unique($borderDays);
        rsort($finalBorderDays, SORT_REGULAR);
        $goodDate=null;

        foreach($finalBorderDays as $finalBorderDay){
            $countStudents=StudentCourse::where('teacher_course_id', $courseId)->where("start_date", "<=" ,$finalBorderDay)->where("end_date",">=",$finalBorderDay)->count();

            if($countStudents >= $getCourseInfo->student_limit){
                return ["message"=>"error", "goodDate"=>$goodDate?:null];
            }else{
                $goodDate=$finalBorderDay;
            }
        }
        return ["message"=>"success"];
    }

}
