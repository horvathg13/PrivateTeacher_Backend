<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialWorkDays extends Model
{
    use HasFactory;
    protected $table='special_work_days';
    protected $fillable=[
        'name',
        'start',
        'end',
        'school_id',
        'school_year_id'
    ];

    public $timestamps=false;
}
