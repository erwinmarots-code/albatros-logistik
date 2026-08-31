<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_request_id',
        'spare_part_id',
        'quantity',
        'odometer_before',
        'odometer_after',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'odometer_before' => 'integer',
        'odometer_after' => 'integer',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}