<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ErrorLogs extends Model
{
    use HasFactory;
    protected $table='error_logs';
    protected $fillable=[
        'user_id',
        'procedure_name',
        'status_code',
        'message',
        'debug_backtrace',
    ];

    public function user(): HasOne{
        return $this->hasOne(User::class,'id','user_id');
    }
}
