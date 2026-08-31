<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'license_number',
        'phone',
        'address',
        'status',
        'branch_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    /**
     * Get the branch where the driver belongs.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all delivery tasks assigned to this driver.
     */
    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class);
    }

    /**
     * Get the user who created this driver (if any).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope a query to only include drivers that are available (not on duty).
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->whereDoesntHave('deliveryTasks', function ($q) {
                $q->whereIn('status', ['assigned', 'in_progress']);
            });
    }

    /**
     * Scope a query to only include drivers that are currently on duty.
     */
    public function scopeOnDuty($query)
    {
        return $query->whereHas('deliveryTasks', function ($q) {
            $q->whereIn('status', ['assigned', 'in_progress']);
        });
    }

    /**
     * Scope a query to only include drivers that are not on duty.
     */
    public function scopeNotOnDuty($query)
    {
        return $query->whereDoesntHave('deliveryTasks', function ($q) {
            $q->whereIn('status', ['assigned', 'in_progress']);
        });
    }

    // ============================================================
    // ATTRIBUTES / ACCESSORS
    // ============================================================

    /**
     * Check if the driver has an active task (assigned or in_progress).
     */
    public function hasActiveTask(): bool
    {
        return $this->deliveryTasks()
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();
    }

    /**
     * Get the status attribute with automatic 'on_duty' override.
     */
    public function getStatusAttribute($value)
    {
        // If driver has an active task, force status to 'on_duty'
        if ($this->hasActiveTask()) {
            return 'on_duty';
        }

        return $value;
    }

    /**
     * Get the status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'available'   => 'Tersedia',
            'on_duty'     => 'Bertugas',
            'unavailable' => 'Tidak Tersedia',
            'off'         => 'Off',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status badge class for styling.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'available'   => 'badge-success',
            'on_duty'     => 'badge-warning',
            'unavailable' => 'badge-danger',
            'off'         => 'badge-secondary',
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Check if the driver can be assigned to a new task.
     */
    public function isAvailableForTask(): bool
    {
        return $this->status === 'available' && !$this->hasActiveTask();
    }
}