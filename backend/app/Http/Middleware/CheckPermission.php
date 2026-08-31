<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Gate::allows('view-menu', $permission)) {
            abort(403, 'Anda tidak memiliki akses ke menu ini.');
        }
        return $next($request);
    }
}