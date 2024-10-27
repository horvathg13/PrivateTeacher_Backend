<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLocation extends Model
{
    protected $table = 'teacher_location';
    public $timestamps=false;

    protected $fillable=[
        "teacher_id",
        "location_id"
    ];
}
