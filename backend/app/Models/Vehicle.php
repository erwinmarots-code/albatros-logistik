<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'brand',
        'model',
        'year',
        'color',
        'engine_capacity',
        'fuel_type',
        'status',
        'purchase_date',
        'price',
        'notes'
    ];
}