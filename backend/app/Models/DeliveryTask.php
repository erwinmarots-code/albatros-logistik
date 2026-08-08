<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_project_id',
        'client_id',
        'vehicle_id',
        'driver_id',
        'task_date',
        'departure_time',
        'estimated_return_time',
        'actual_return_time',
        'origin',
        'destination',
        'description',
        'distance_km',
        'status',
        'notes',
        'created_by',
    ];

    public function shippingProject()
    {
        return $this->belongsTo(ShippingProject::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fuelExpenses()
    {
        return $this->hasMany(FuelExpense::class);
    }
}