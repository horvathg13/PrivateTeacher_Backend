<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLocations extends Model
{
    public $table='course_locations';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'location_id',
    ];
}
