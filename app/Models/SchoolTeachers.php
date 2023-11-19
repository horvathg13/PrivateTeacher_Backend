<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolTeachers extends Model
{
    use HasFactory;
    protected $table='school_teachers';
    protected $fillable=[
        'school_id',
        'teacher_id'
    ];
}
