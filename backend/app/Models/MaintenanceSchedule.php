<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'type',           // oil_change, tire_replacement, sparepart, general
        'description',
        'last_date',
        'next_date',
        'mileage_interval',
        'estimated_cost',
        'status',         // scheduled, done, cancelled
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}