<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Get paginated users with search and filter
     */
    public function getUsers(array $params = []): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 10;
        $search = $params['search'] ?? null;
        $roleFilter = $params['role'] ?? null;

        $query = User::with(['roles', 'permissions']);

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($roleFilter) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create new user with role
     */
    public function createUser(array $data): User
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Create user
        $user = User::create($data);

        // Assign role
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user->load(['roles', 'permissions']);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): User
    {
        return User::with(['roles', 'permissions'])->findOrFail($id);
    }

    /**
     * Update user data
     */
    public function updateUser(User $user, array $data): User
    {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Remove password from data if empty
            unset($data['password']);
        }

        // Update user
        $user->update($data);

        // Update role if provided
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->load(['roles', 'permissions']);
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user, User $currentUser): bool
    {
        // Prevent deleting self
        if ($currentUser->id === $user->id) {
            throw new \Exception('Tidak dapat menghapus user sendiri');
        }

        return $user->delete();
    }

    /**
     * Get available roles
     */
    public function getRoles(): \Illuminate\Support\Collection
    {
        return Role::all()->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => ucfirst(str_replace('-', ' ', $role->name)),
                'permissions_count' => $role->permissions->count()
            ];
        });
    }

    /**
     * Check if user can be deleted
     */
    public function canDeleteUser(User $user, User $currentUser): bool
    {
        // Cannot delete self
        if ($currentUser->id === $user->id) {
            return false;
        }

        // Cannot delete if user has critical data (optional business rule)
        // Add more business rules here if needed

        return true;
    }

    /**
     * Get user statistics
     */
    public function getUserStats()
    {
        $roles = Role::all();
        $roleStats = $roles->map(function ($role) {
            return [
                'role' => $role->name,
                'count' => User::role($role->name)->count()
            ];
        });

        return [
            'total_users' => User::count(),
            'active_users' => User::where('aktif', true)->count(),
            'inactive_users' => User::where('aktif', false)->count(),
            'by_role' => $roleStats
        ];
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function toggleUserStatus(User $user): User
    {
        $user->update(['aktif' => !$user->aktif]);
        return $user->load(['roles', 'permissions']);
    }
}