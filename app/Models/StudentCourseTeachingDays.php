<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCourseTeachingDays extends Model
{
    protected $table='teacher_course_requests';
    protected $fillable=[
        'student_course_id',
        'teaching_day',
        'from',
        'to'
    ];
    public $timestamps=false;
}
