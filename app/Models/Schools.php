<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schools extends Model
{
    use HasFactory;
    protected $table='schools';
    public $timestamps = false;
    protected $fillable=[
        'name',
        'country',
        'zip',
        'city',
        'street',
        'number'
    ];
}
