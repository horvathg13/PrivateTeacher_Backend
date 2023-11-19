<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildrenConnections extends Model
{
    use HasFactory;
    protected $table='children_connections';
    protected $fillable=[
        'parent_id',
        'child_id',
    ];
}
