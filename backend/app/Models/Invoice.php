<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'shipping_project_id',
        'client_id',
        'total_amount',
        'due_date',
        'status',
        'branch_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function shippingProject()
    {
        return $this->belongsTo(ShippingProject::class, 'shipping_project_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}