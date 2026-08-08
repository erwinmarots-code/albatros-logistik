<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'no_po',
        'resi_number',
        'sender_name',
        'sender_address',
        'sender_phone',
        'receiver_name',
        'receiver_address',
        'receiver_phone',
        'goods_description',
        'weight_kg',
        'collie',
        'volumetric',
        'goods_value',
        'shipping_method',
        'status',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class);
    }

    // Relasi ke invoice
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}