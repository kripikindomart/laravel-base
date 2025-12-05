<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRolesAndPermissions
{
    /**
     * Get all roles for the user
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    /**
     * Get all direct permissions for the user
     */
    public function permissions(): BelongsToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions', 'model_id', 'permission_id');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role|string|array $roles): self
    {
        $roles = collect($roles)->map(function ($role) {
            if (is_string($role)) {
                return Role::where('slug', $role)
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail();
            }
            return $role;
        });

        $this->roles()->syncWithoutDetaching($roles->pluck('id'));

        return $this;
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role|string $role): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)
                ->where('tenant_id', $this->tenant_id)
                ->firstOrFail();
        }

        $this->roles()->detach($role->id);

        return $this;
    }

    /**
     * Give permission directly to user
     */
    public function givePermissionTo(Permission|string|array $permissions): self
    {
        $permissions = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('slug', $permission)
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail();
            }
            return $permission;
        });

        $this->permissions()->syncWithoutDetaching($permissions->pluck('id'));

        return $this;
    }

    /**
     * Revoke permission from user
     */
    public function revokePermissionTo(Permission|string $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)
                ->where('tenant_id', $this->tenant_id)
                ->firstOrFail();
        }

        $this->permissions()->detach($permission->id);

        return $this;
    }

    /**
     * Check if user has role
     */
    public function hasRole(Role|string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roles = collect($roles);

        return $this->roles->contains(function ($role) use ($roles) {
            return $roles->contains($role->slug) || $roles->contains($role->id);
        });
    }

    /**
     * Check if user has any of the roles
     */
    public function hasAnyRole(array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(Permission|string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check direct permissions
        if ($this->hasDirectPermission($permission)) {
            return true;
        }

        // Check role permissions
        return $this->hasPermissionViaRole($permission);
    }

    /**
     * Check if user has direct permission
     */
    public function hasDirectPermission(Permission|string $permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions->contains('slug', $permission);
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Check if user has permission through role
     */
    public function hasPermissionViaRole(Permission|string $permission): bool
    {
        $permissionSlug = is_string($permission) ? $permission : $permission->slug;

        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->contains('slug', $permissionSlug);
    }

    /**
     * Get all permissions (direct + via roles)
     */
    public function getAllPermissions(): Collection
    {
        $directPermissions = $this->permissions;

        $rolePermissions = $this->roles->flatMap(function ($role) {
            return $role->permissions;
        });

        return $directPermissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Sync roles (replace all roles)
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            if (is_string($role)) {
                return Role::where('slug', $role)
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail()->id;
            }
            return is_object($role) ? $role->id : $role;
        });

        $this->roles()->sync($roleIds);

        return $this;
    }

    /**
     * Sync permissions (replace all permissions)
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('slug', $permission)
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail()->id;
            }
            return is_object($permission) ? $permission->id : $permission;
        });

        $this->permissions()->sync($permissionIds);

        return $this;
    }
}
