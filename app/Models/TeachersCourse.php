<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachersCourse extends Model
{
    use HasFactory;
    protected $table='teachers_course';
    protected $fillable=[
        'teacher_id',
        'course_id',
        'payment_period'
    ];
    public $timestamps=false;
}
