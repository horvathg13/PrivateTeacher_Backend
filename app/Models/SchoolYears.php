<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolYears extends Model
{
    use HasFactory;
    protected $table='school_years';
    protected $fillable=[
        'year',
        'school_id',
        'name',
        'start',
        'end',
        'year_status'
    ];
    protected $dates = ['startDate', 'endDate'];
    public $timestamps=false;
}
