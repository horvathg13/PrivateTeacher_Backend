<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolBreaks extends Model
{
    use HasFactory;
    protected $table='school_breaks';
    protected $fillable=[
        'name',
        'start',
        'end',
        'school_id',
        'school_year_id'
    ];
    public $timestamps=false;
}
