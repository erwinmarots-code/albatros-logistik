<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'stock',
        'min_stock',
        'price',
        'lifespan_km',
        'lifespan_months',
        'status',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'price' => 'decimal:2',
        'lifespan_km' => 'integer',
        'lifespan_months' => 'integer',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements()
    {
        return $this->hasMany(SparePartMovement::class);
    }

    public function maintenanceItems()
    {
        return $this->hasMany(MaintenanceRequestItem::class);
    }

    // ============================================================
    // SCOPE
    // ============================================================

    /**
     * Scope untuk menampilkan spare part yang aktif (tidak rusak / stok habis).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rusak_tidak_layak', 'stok_habis']);
    }

    // ============================================================
    // METHOD
    // ============================================================

    /**
     * Update status spare part berdasarkan stok saat ini.
     */
    public function updateStatus()
    {
        if ($this->status === 'rusak_tidak_layak') {
            return;
        }

        if ($this->category === 'sekali_pakai') {
            if ($this->stock <= 0) {
                $this->status = 'stok_habis';
            } elseif ($this->stock <= $this->min_stock) {
                $this->status = 'perlu_restok';
            } else {
                $this->status = 'tersedia';
            }
        }
        // Untuk spare part berulang, status diatur manual (sedang_dipakai, dll.)

        $this->save();
    }
}