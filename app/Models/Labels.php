<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Labels extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table='labels';
    protected $fillable=['label'];


}
