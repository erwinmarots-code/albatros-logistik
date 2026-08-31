<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Branchable
{
    /**
     * Boot the trait: add global scope untuk filter cabang
     */
    public static function bootBranchable()
    {
        // Global scope: hanya untuk user yang bukan super_admin
        static::addGlobalScope('branch', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role !== 'super_admin' && $user->branch_id) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $user->branch_id);
            }
        });
    }

    /**
     * Set branch_id otomatis saat creating (jika belum diisi)
     */
    public static function bootBranchableCreating()
    {
        static::creating(function ($model) {
            if (empty($model->branch_id)) {
                $user = auth()->user();
                if ($user && $user->branch_id) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });
    }
}