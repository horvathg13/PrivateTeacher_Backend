<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schools extends Model
{
    use HasFactory;
    protected $table='schools';
    public $timestamps = false;
    protected $fillable=[
        'name',
        'country',
        'zip',
        'city',
        'street',
        'number'
    ];

    public function location(): BelongsToMany
    {
        return $this->belongsToMany(Locations::class,"school_locations", "school_id", "location_id");
    }
}
