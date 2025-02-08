<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TerminationCourseRequests extends Model
{
    protected $table='termination_course_requests';
    protected $fillable=[
        'student_course_id',
        'from',
        'status'
    ];
    public $timestamps=true;

    public function childInfo():HasOneThrough
    {
        return $this->hasOneThrough(Children::class, StudentCourse::class, "id", "id", "student_course_id", "child_id");
    }
    public function request():MorphOne
    {
        return $this->morphOne(CommonRequests::class, 'requestable');
    }

    public function courseNamesAndLangs():HasManyThrough
    {
        return $this->hasManyThrough(CourseLangsNames::class,StudentCourse::class, "id", "course_id", "student_course_id", "teacher_course_id");
    }


}
