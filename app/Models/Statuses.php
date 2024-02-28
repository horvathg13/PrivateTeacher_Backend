<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Statuses extends Model
{
    use HasFactory;

    protected $table='statuses';
    protected $fillable = [
        'status'
    ];
    public $timestamps = false;

    public function course():HasOne
    {
        return $this->hasOne(CourseInfos::class);
    }

}
