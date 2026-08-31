<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_task_id',
        'type',
        'amount',
        'vehicle_id',
        'driver_id',
        'description',
        'transaction_date',
        'status',
        'unique_code',
        'approved_by',
        'approved_at',
        'created_by',
        'branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function deliveryTask()
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_task_id');
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getTypeLabelAttribute()
    {
        $labels = [
            'bahan_bakar' => 'Bahan Bakar',
            'toll' => 'Tol',
            'parkir' => 'Parkir',
            'lainnya' => 'Lainnya',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];
        return $labels[$this->status] ?? $this->status;
    }
}