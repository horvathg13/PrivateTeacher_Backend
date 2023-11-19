<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingDays extends Model
{
    use HasFactory;
    protected $table='teaching_days';
    protected $fillable=[
        'name',
        'teacher_id',
        'start',
        'end',
        'location_id'
    ];
}
