<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Messages extends Model
{
    use HasFactory;
    protected $table='messages';
    protected $fillable=[
        'teacher_course_request_id',
        'sender_id',
        'receiver_id',
        'message'
    ];

    public function courseInfo(): HasOneThrough{
        return $this->hasOneThrough(CourseInfos::class,TeacherCourseRequests::class,'id', 'id',"teacher_course_request_id", 'teacher_course_id');
    }

    public function senderInfo(): HasOne{
        return $this->hasOne(User::class,'id','sender_id');
    }

    public function childInfo(): HasOneThrough{
        return $this->hasOneThrough(Children::class, TeacherCourseRequests::class, 'id', 'id', 'teacher_course_request_id', 'child_id');
    }
}
