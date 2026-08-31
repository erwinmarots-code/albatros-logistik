<?php

namespace App\Traits;

trait BranchScopeTrait
{
    protected function applyBranchFilter($query)
    {
        $user = auth()->user();
        if ($user && $user->role !== 'super_admin') {
            $query->where('branch_id', $user->branch_id);
        }
        return $query;
    }

    protected function setBranchIdIfNeeded(&$data)
    {
        $user = auth()->user();
        if ($user && $user->role !== 'super_admin') {
            $data['branch_id'] = $user->branch_id;
        }
        return $data;
    }
}