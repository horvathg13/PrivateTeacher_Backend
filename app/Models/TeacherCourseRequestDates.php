<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherCourseRequestDates extends Model
{
    use HasFactory;
    protected $table='teacher_course_request_dates';
    protected $fillabel= [
        'teacher_course_request_id',
        'teaching_day_id',
        'collapsed_lesson',
        'status'
    ];
}
