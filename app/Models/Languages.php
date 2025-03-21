<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Languages extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table='languages';
    protected $fillable=[
        "value",
        "label"
    ];}
