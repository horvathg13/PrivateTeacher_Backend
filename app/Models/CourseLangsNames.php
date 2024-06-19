<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLangsNames extends Model
{
    public $timestamps = false;
    protected $table = 'course_langs_names';
    protected $primaryKey = 'id';
    protected $fillable = ['course_id', 'lang', 'names'];

}
