<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPeriods extends Model
{
    use HasFactory;
    protected $table='payment_periods';
    protected $fillable='period';
}
