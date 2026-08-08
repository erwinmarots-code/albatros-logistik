<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'po_number',
        'invoice_number',
        'sender_name',
        'sender_address',
        'sender_phone',
        'receiver_name',
        'receiver_address',
        'receiver_phone',
        'goods_description',
        'weight_kg',
        'collie',
        'volumetric',
        'ppn',
        'discount',
        'insurance',
        'goods_value',
        'shipping_mode',
        'status',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class, 'project_id');
    }

    // Auto generate invoice number
    public static function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $last = self::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
        return 'INV-' . $year . $month . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}