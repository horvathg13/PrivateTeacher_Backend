<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherCourseRequests extends Model
{
    use HasFactory;
    protected $table='teacher_course_requests';
    protected $fillable=[
        'child_id',
        'teacher_course_id',
        'number_of_lessons',
        'status',
        'notice'
    ];
    public $timestamps=true;
}
