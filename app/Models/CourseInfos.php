<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseInfos extends Model
{
    use HasFactory;
    protected $table='course_infos';
    protected $fillable=[
        'name',
        'subject',
        'student_limit',
        'minutes_lesson',
        'min_teaching_day',
        'double_time',
        'course_price_per_lesson',
        'status_id', 
        'school_id',
        'school_year_id'
    ];
    public $timestamps = false;

    public function status():HasOne 
    {
        return $this->hasOne(Statuses::class, "id", "status_id");
    }
}
