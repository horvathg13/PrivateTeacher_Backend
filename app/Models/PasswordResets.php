<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PasswordResets extends Model
{
    use HasFactory;
    protected $table='password_resets';
    protected $fillable = [
        'email',
        "token"
    ];

    public $timestamps = false;
}
