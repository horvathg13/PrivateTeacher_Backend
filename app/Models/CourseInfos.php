<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseInfos extends Model
{
    use HasFactory;
    protected $table='course_infos';
    protected $fillable=[
        'name',
        'subject',
        'student_limit',
        'minutes/lesson',
        'min_teaching_day',
        'double_time',
        'course_price_per_lesson',
        'status', 
        'school_id',
        'school_year_id'
    ];
}
