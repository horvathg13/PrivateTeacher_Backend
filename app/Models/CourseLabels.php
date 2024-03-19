<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLabels extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table='course_labels';
    protected $fillable=['course_id', 'label_id'];
}
