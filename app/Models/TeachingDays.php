<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeachingDays extends Model
{
    use HasFactory;
    protected $table='teaching_days';
    protected $fillable=[
        'day',
        'teacher_id',
        'course_id',
        'course_location_id'
    ];

    public function teacher():HasOne
    {
        return $this->hasOne(User::class,'id', 'teacher_id');
    }
}
