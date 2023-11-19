<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Children extends Model
{
    use HasFactory;
    protected $table='children';
    protected $fillable=[
        'first_name',
        'last_name',
        'username',
        'password',
        'birthday',
    ];
}
