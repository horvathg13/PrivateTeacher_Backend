<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraLessonTypes extends Model
{
    use HasFactory;
    protected $table='extra_lesson_types';
    protected $fillable=['type'];
}
