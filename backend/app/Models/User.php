<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'branch_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ===== RELASI =====
    public function branch() { return $this->belongsTo(Branch::class); }

    // Role checks
    public function isSuperAdmin() { return $this->role === 'super_admin'; }
    public function isAdminTransport() { return $this->role === 'admin_transport'; }
    public function isAdminFinance() { return $this->role === 'admin_finance'; }
    public function isAdminProject() { return $this->role === 'admin_project'; }
    public function isStaff() { return $this->role === 'staff'; }

    // Relasi ke data lain (sudah ada di file Anda)
    public function createdProjects() { return $this->hasMany(ShippingProject::class, 'created_by'); }
    public function createdDeliveryTasks() { return $this->hasMany(DeliveryTask::class, 'created_by'); }
    public function createdFuelExpenses() { return $this->hasMany(FuelExpense::class, 'created_by'); }
    public function approvedFuelExpenses() { return $this->hasMany(FuelExpense::class, 'approved_by'); }
    public function createdMaintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'created_by'); }
    public function approvedMaintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'approved_by'); }
    public function executedMaintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'executed_by'); }
    public function createdSpareParts() { return $this->hasMany(SparePart::class, 'created_by'); }

    // ===== PERMISSION METHODS =====
    public function getPermissionsAttribute()
    {
        return DB::table('role_permission')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('role_permission.role', $this->role)
            ->pluck('permissions.name')
            ->toArray();
    }

    public function hasPermission($permissionName)
    {
        if ($this->isSuperAdmin()) return true;
        return in_array($permissionName, $this->permissions);
    }

    public function hasAnyPermission(array $permissionNames)
    {
        foreach ($permissionNames as $perm) {
            if ($this->hasPermission($perm)) return true;
        }
        return false;
    }

    public function hasAllPermissions(array $permissionNames)
    {
        foreach ($permissionNames as $perm) {
            if (!$this->hasPermission($perm)) return false;
        }
        return true;
    }
}