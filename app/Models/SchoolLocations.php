<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class SchoolLocations extends Model
{
    public $timestamps = false;
    public $table="school_locations";
    protected $fillable=[
        "school_id",
        "location_id",
        "name"
    ];
    protected $primaryKey = "location_id";
    public $incrementing = false;
    protected $keyType = 'string';

}
