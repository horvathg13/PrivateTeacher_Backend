<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locations extends Model
{
    use HasFactory;
    protected $table='locations';
    protected $fillable=[
        'name',
        'country',
        'zip',
        'street',
        'number'
    ];
}
