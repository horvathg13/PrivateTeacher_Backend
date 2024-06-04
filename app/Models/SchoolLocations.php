<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolLocations extends Model
{
    public $timestamps = false;
    public $table="school_locations";
    protected $fillable=[
        "school_id",
        "location_id",
        "name"
    ];
    protected $primaryKey = null;
    public $incrementing = false;
}
