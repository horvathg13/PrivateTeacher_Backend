<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MakeUpClasses extends Model
{
    use HasFactory;
    protected $table='make_up_classes';
    protected $fillable = [
        'canceled_class_id',
        'new_start',
        'new_end',
        'status'
    ];
}
