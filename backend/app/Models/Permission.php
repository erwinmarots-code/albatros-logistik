<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'group',
    ];

    /**
     * Relasi ke role (many-to-many via role_permission)
     */
    public function roles()
    {
        return $this->belongsToMany(User::class, 'role_permission', 'permission_id', 'role', 'id', 'role');
    }
}