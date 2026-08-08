<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_project_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'status',
        'notes',
        'created_by',
    ];

    public function shippingProject()
    {
        return $this->belongsTo(ShippingProject::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate invoice number otomatis
     * Format: INV-YYYYMMDD-XXXX
     */
    public static function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ymd');
        $last = self::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        if ($last) {
            $lastNumber = intval(substr($last->invoice_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        return $prefix . '-' . $newNumber;
    }
}