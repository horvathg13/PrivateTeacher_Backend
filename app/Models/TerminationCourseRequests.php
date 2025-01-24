<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TerminationCourseRequests extends Model
{
    protected $table='teacher_course_requests';
    protected $fillable=[
        'student_course_id',
        'message',
        'from',
        'status'
    ];
    public $timestamps=true;

}
