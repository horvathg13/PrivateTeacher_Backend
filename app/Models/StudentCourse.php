<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class StudentCourse extends Model
{

    protected $table = 'student_course';

    protected $fillable=[
        'teacher_course_request_id',
        'child_id',
        'teacher_course_id',
        'start_date',
        'end_date'
    ];

    public function courseInfos():HasOne
    {
        return $this->hasOne(CourseInfos::class,"id", "teacher_course_id");
    }
    public function parentInfo():hasManyThrough{
        return $this->hasManyThrough(User::class, ChildrenConnections::class,'child_id', 'id', 'child_id','parent_id');
    }
    public function childInfo():HasOne
    {
        return $this->hasOne(Children::class, "id", "child_id");
    }

    public function teachingDays():HasMany
    {
        return $this->hasMany(StudentCourseTeachingDays::class, "student_course_id", "id");
    }
}
