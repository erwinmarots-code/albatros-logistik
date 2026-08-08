<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_code',
        'delivery_task_id',
        'vehicle_id',
        'driver_id',
        'type',
        'amount',
        'description',
        'receipt_photo',
        'request_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    public function deliveryTask()
    {
        return $this->belongsTo(DeliveryTask::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate unique code dengan format UPG-YYMM-XXXX
     * Contoh: UPG-2408-0001
     */
    public static function generateUniqueCode()
    {
        $prefix = 'UPG-' . date('ym') . '-';
        $last = self::where('unique_code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        if ($last) {
            $lastNumber = intval(substr($last->unique_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        return $prefix . $newNumber;
    }
}