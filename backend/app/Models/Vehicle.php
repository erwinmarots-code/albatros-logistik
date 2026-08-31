<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plate_number',
        'brand',
        'model',
        'year',
        'color',
        'engine_capacity',
        'fuel_type',
        'status',
        'price',
        'branch_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year' => 'integer',
        'engine_capacity' => 'decimal:2',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope a query to only include vehicles that are available (not in use).
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->whereDoesntHave('deliveryTasks', function ($q) {
                $q->whereIn('status', ['assigned', 'in_progress']);
            });
    }

    /**
     * Scope a query to only include vehicles that are currently in use.
     */
    public function scopeInUse($query)
    {
        return $query->whereHas('deliveryTasks', function ($q) {
            $q->whereIn('status', ['assigned', 'in_progress']);
        });
    }

    /**
     * Scope a query to only include vehicles that are not in use.
     */
    public function scopeNotInUse($query)
    {
        return $query->whereDoesntHave('deliveryTasks', function ($q) {
            $q->whereIn('status', ['assigned', 'in_progress']);
        });
    }

    // ============================================================
    // ATTRIBUTES / ACCESSORS
    // ============================================================

    /**
     * Check if the vehicle has an active task (assigned or in_progress).
     */
    public function hasActiveTask(): bool
    {
        return $this->deliveryTasks()
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();
    }

    /**
     * Get the status attribute with automatic 'in_use' override.
     */
    public function getStatusAttribute($value)
    {
        // If vehicle has an active task, force status to 'in_use'
        if ($this->hasActiveTask()) {
            return 'in_use';
        }

        return $value;
    }

    /**
     * Get the status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'available'    => 'Tersedia',
            'in_use'       => 'Sedang Dipakai',
            'maintenance'  => 'Perawatan',
            'inactive'     => 'Tidak Aktif',
            'rented'       => 'Disewa',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status badge class for styling.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'available'    => 'badge-success',
            'in_use'       => 'badge-warning',
            'maintenance'  => 'badge-info',
            'inactive'     => 'badge-secondary',
            'rented'       => 'badge-primary',
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Check if the vehicle can be assigned to a new task.
     */
    public function isAvailableForTask(): bool
    {
        return $this->status === 'available' && !$this->hasActiveTask();
    }
}