<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeacherLocation extends Model
{
    protected $table = 'teacher_location';
    public $timestamps=false;

    protected $fillable=[
        "teacher_id",
        "location_id"
    ];

    public function locationInfo():HasOne
    {
        return $this->hasOne(Locations::class, "id", "location_id");
    }
}
