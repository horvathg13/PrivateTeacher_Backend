<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Locations extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $table='locations';
    protected $fillable=[
        'name',
        'country',
        'city',
        'zip',
        'street',
        'number',
        "floor",
        "door",
    ];

}
