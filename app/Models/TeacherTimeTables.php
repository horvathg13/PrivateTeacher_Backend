<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherTimeTables extends Model
{
    use HasFactory;
    protected $table='teacher_time_tables';
    protected $fillable = [
        'from',
        'to',
        'teacher_course_request_id',
    ];
}
