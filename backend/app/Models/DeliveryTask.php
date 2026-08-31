<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'vehicle_id',
        'driver_id',
        'client_id',
        'no_resi',
        'tanggal',
        'delivery_date',
        'status',
        'notes',
        'receiver_name',
        'receiver_address',
        'receiver_phone',
        'goods_description',
        'weight_kg',
        'collie',
        'created_by',
        'branch_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'delivery_date' => 'date',
        'weight_kg' => 'decimal:2',
        'collie' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(ShippingProject::class, 'project_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fuelExpenses()
    {
        return $this->hasMany(FuelExpense::class, 'delivery_task_id');
    }
}