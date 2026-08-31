<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePartMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'spare_part_id',
        'quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}