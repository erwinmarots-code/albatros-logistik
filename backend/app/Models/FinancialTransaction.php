<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'type',
        'category',
        'amount',
        'description',
        'vehicle_id',
        'driver_id',
        'client_id',
        'created_by',
        'status',
        'po_number',
        'maintenance_number',
        'reference_type',
        'reference_id',
        'branch_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // ============================================================
    // RELASI
    // ============================================================

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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Polymorphic relation to FuelExpense or MaintenanceRequest.
     */
    public function reference()
    {
        return $this->morphTo();
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // ============================================================
    // ATTRIBUTES
    // ============================================================

    public function getStatusLabelAttribute()
    {
        $labels = [
            'confirmed' => 'Confirmed',
            'pending'   => 'Pending',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}