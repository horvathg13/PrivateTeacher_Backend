<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePayments extends Model
{
    use HasFactory;
    protected $table='course_payments';
    protected $fillable=[
        'teacher_course_id',
        'child_id',
        'paid',
    ];
}
