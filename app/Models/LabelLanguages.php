<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabelLanguages extends Model
{
    public $timestamps = false;

    public $table="label_languages";

    protected $fillable = [
        'label_id',
        'language_id',
    ];
}
