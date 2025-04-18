<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRoles extends Model
{
    use HasFactory;

    protected $table='userRoles';
    protected $fillable=[
        "user_id",
        "role_id",
        "reference_id",
    ];

    public $timestamps=false;
    public function user(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
