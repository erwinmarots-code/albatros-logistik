<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingProject extends Model
{
    use HasFactory;

    public static $statuses = ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled'];

    protected $fillable = [
        'client_id',
        'branch_id',
        'no_po',
        'no_resi',
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
        'contract_value',
        'shipping_method',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'collie' => 'integer',
        'contract_value' => 'decimal:2',
        'goods_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class, 'project_id');
    }

    /**
     * 🔥 Relasi ke invoice (satu project memiliki satu invoice)
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'shipping_project_id');
    }

    // ============================================================
    // HELPER
    // ============================================================

    public static function generateResiNumber($branchCode)
    {
        $prefix = strtoupper($branchCode);
        $date = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return $prefix . '-' . $date . '-' . $random;
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }
}