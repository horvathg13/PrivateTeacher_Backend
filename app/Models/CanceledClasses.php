<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanceledClasses extends Model
{
    use HasFactory;
    protected $table='canceled_classes';
    protected $fillable=[
        'teacher_time_table_id',
        'canceled_by',
    ];
}
