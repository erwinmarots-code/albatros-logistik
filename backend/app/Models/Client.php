<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'pic_name',
        'npwp',
        'branch_id',
    ];

    public function projects()
    {
        return $this->hasMany(ShippingProject::class);
    }
}