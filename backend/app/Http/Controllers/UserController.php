<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use BranchScopeTrait;

    public function index(Request $request)
    {
        try {
            $query = User::with('branch');
            $user = auth()->user();
            if ($user->role !== 'super_admin') {
                $query->where('branch_id', $user->branch_id);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('role', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->orderBy('id', 'desc')->get();

            // 🔥 Sembunyikan atribut 'permissions' agar tidak ikut di-response
            $users->each(function ($u) {
                $u->makeHidden('permissions');
            });

            return response()->json(['data' => $users]);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data user'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $currentUser = auth()->user();
            if ($currentUser->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat membuat akun'], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required|in:super_admin,admin_project,admin_transport,admin_finance,branch_admin,staff',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'branch_id' => $request->branch_id,
            ]);

            return response()->json([
                'message' => 'User berhasil ditambahkan',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan user: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $currentUser = auth()->user();
            $user = User::with('branch')->findOrFail($id);

            if ($currentUser->role !== 'super_admin' && $user->branch_id !== $currentUser->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke user ini'], 403);
            }

            // 🔥 Sembunyikan 'permissions' di detail
            $user->makeHidden('permissions');

            return response()->json(['data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $currentUser = auth()->user();
            if ($currentUser->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat mengedit akun'], 403);
            }

            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6|confirmed',
                'role' => 'sometimes|in:super_admin,admin_project,admin_transport,admin_finance,branch_admin,staff',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->only(['name', 'email', 'role', 'branch_id']);
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            $user->makeHidden('permissions');

            return response()->json([
                'message' => 'User berhasil diperbarui',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui user'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $currentUser = auth()->user();
            if ($currentUser->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat menghapus akun'], 403);
            }

            $user = User::findOrFail($id);
            if ($user->id === $currentUser->id) {
                return response()->json(['message' => 'Tidak dapat menghapus akun sendiri'], 400);
            }

            $user->delete();
            return response()->json(['message' => 'User berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus user'], 500);
        }
    }

    // ============================================================
    // 🔥 PERMISSION METHODS
    // ============================================================

    public function getPermissions()
    {
        try {
            $permissions = Permission::orderBy('group')->orderBy('name')->get();
            return response()->json(['data' => $permissions]);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat permission'], 500);
        }
    }

    public function getRolePermissions($role)
    {
        try {
            $permissionNames = DB::table('role_permission')
                ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
                ->where('role_permission.role', $role)
                ->pluck('permissions.name')
                ->toArray();

            return response()->json(['data' => $permissionNames]);
        } catch (\Exception $e) {
            Log::error('Error fetching role permissions: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat permission role'], 500);
        }
    }

    public function updateRolePermissions(Request $request, $role)
    {
        try {
            $currentUser = auth()->user();
            if ($currentUser->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat mengelola permission'], 403);
            }

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $permissionNames = $request->permissions;
            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();

            DB::table('role_permission')->where('role', $role)->delete();

            foreach ($permissionIds as $permId) {
                DB::table('role_permission')->insert([
                    'role' => $role,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'message' => 'Permission untuk role ' . $role . ' berhasil diperbarui',
                'data' => $permissionNames,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating role permissions: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui permission: ' . $e->getMessage()], 500);
        }
    }
}