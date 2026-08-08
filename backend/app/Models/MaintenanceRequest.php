<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'schedule_id',
        'request_date',
        'description',
        'service_type',      // <-- tambahkan ini
        'estimated_cost',
        'urgency',
        'status',
        'approved_by',
        'approved_at',
        'actual_cost',
        'notes',
        'executed_at',
        'is_executed',
        'executed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'is_executed' => 'boolean',
    ];

    /**
     * Get the vehicle that owns the maintenance request.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver that submitted the request.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the maintenance schedule associated with the request (optional).
     */
    public function schedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'schedule_id');
    }

    /**
     * Get the admin finance who approved/rejected the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who marked the maintenance as executed (completed).
     */
    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}