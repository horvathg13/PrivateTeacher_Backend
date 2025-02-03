<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommonRequests extends Model
{
    protected $table="common_requests";
    protected $fillable=[
        "message",
        "status",
        "requestable_id",
        "requestable_type"
    ];

    public function requestable():MorphTo
    {
        return $this->morphTo();
    }
}
