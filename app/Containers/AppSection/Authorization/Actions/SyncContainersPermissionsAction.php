<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tasks\FindRoleTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Exception;

final class SyncContainersPermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(bool $dry, bool $prune): void
    {
        if (!app()->runningInConsole()) {
            throw new AuthorizationException('This action can only be run in console');
        }

        $manifests = $this->loadAndValidateManifests(); // JSON Schema + dedupe by id
        $byId = collect($manifests)->keyBy('id');

        // Upsert permissions
        foreach ($byId as $perm) {
            $tmpPerm = Permission::updateOrCreate(
                ['id' => $perm['id']],
                [
                    'name' => $perm['name'],
                    'display_name' => $perm['display_name'] ?? null,
                    'description' => $perm['description'] ?? null,
                    'group' => $perm['group'] ?? null,
                    'is_active' => !($perm['deprecated'] ?? false),
                    'version' => $perm['version'],
                    'guard_name' => 'api',
                ]
            );


            // Default roles (additive)
            foreach (($perm['default_roles'] ?? []) as $roleName) {
                /** @var Role $role */
                $role = Role::firstOrCreate(['name' => $roleName]);
                $role->givePermissionTo($tmpPerm);
            }
        }

        // Handle missing permissions
        $existing = Permission::all()->keyBy('id');

        $missing = $existing->keys()->diff($byId->keys());

        foreach ($missing as $uuid) {
            $perm = $existing[$uuid];
            if ($prune) {
                if ($perm->roles()->exists() || $perm->users()->exists()) {
                    // require --force or mark inactive
                    $perm->is_active = false;
                    $perm->save();
                } else {
                    $perm->delete();
                }
            } else {
                $perm->is_active = false;
                $perm->save();
            }
        }

        if ($dry) {
            DB::rollBack();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function loadAndValidateManifests(): array
    {
        $files_paths = config('appSection-authorization.permissions_files');
        $files = [];
        foreach ($files_paths as $file_path) {
            $files = array_merge($files, recursiveGlob($file_path));
        }
        $manifests = [];
        foreach ($files as $file) {
            $permissionsJson = json_decode(file_get_contents($file), true);
            if (!is_array($permissionsJson)) {
                throw new Exception('Invalid JSON file: ' . $file);
            }
            foreach ($permissionsJson as $permission) {
                $this->validateManifest($file, $permission);
                if (array_key_exists($permission['id'], $manifests)) {
                    throw new Exception('Duplicate ID: ' . $permission['id'] . ' in ' . $file);
                }
                $manifests[] = $permission;
            }
        }
        return $manifests;
    }

    private function validateManifest(string $filename, array $manifest): void
    {
        if (!array_key_exists('id', $manifest)) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - id is required');
        }
        if (!is_string($manifest['id'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - id must be a string');
        }
        if (!is_string($manifest['name'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - name must be a string');
        }
        if (!is_string($manifest['display_name'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - display_name must be a string');
        }
        if (!is_string($manifest['description'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - description must be a string');
        }
        if (!is_string($manifest['group'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - group must be a string');
        }
        if (!is_int($manifest['version'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - version must be an integer');
        }
        if (!is_bool($manifest['deprecated'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - deprecated must be a boolean');
        }
        if (!is_array($manifest['default_roles'])) {
            throw new Exception('Invalid JSON file: ' . $filename . ' - default_roles must be an array');
        }
        foreach ($manifest['default_roles'] as $role) {
            if (!is_string($role)) {
                throw new Exception('Invalid JSON file: ' . $filename . ' - default_roles must be an array of strings');
            }
        }
    }
}
