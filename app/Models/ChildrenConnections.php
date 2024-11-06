<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChildrenConnections extends Model
{
    use HasFactory;
    protected $table='children_connections';
    protected $fillable=[
        'parent_id',
        'child_id',
    ];
    public $timestamps=false;

    public function childInfo(): HasOne
    {
        return $this->hasOne(Children::class,'id','child_id');
    }
}
