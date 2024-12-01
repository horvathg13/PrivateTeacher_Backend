<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TeacherCourseRequests extends Model
{
    use HasFactory;
    protected $table='teacher_course_requests';
    protected $fillable=[
        'child_id',
        'teacher_course_id',
        'number_of_lessons',
        'status',
        'notice',
        'teacher_justification'
    ];
    public $timestamps=true;

    public function childInfo(): HasOne{

        return $this->hasOne(Children::class, 'id', 'child_id');
    }
    public function parentInfo():hasManyThrough{
        return $this->hasManyThrough(User::class, ChildrenConnections::class,'child_id', 'id', 'child_id','parent_id');
    }

    public function courseInfo(): hasOne{
        return $this->hasOne(CourseInfos::class,'id', 'teacher_course_id');
    }

    public function courseNamesAndLangs(): HasMany
    {
        return $this->hasMany(CourseLangsNames::class, 'course_id', 'teacher_course_id');
    }


}
