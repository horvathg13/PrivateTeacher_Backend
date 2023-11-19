<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadMessages extends Model
{
    use HasFactory;
    protected $table='read_messages';
    protected $fillable=[
        'read_by',
        'message_id',
    ];
}
