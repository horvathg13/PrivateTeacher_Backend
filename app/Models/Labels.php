<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labels extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table='labels';
    protected $fillable=['label', 'lang'];
}
