<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'city',
        'address',
        'phone',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function shippingProjects()
    {
        return $this->hasMany(ShippingProject::class);
    }

    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class);
    }

    public function fuelExpenses()
    {
        return $this->hasMany(FuelExpense::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function spareParts()
    {
        return $this->hasMany(SparePart::class);
    }
}