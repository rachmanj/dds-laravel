<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission `cancel-ito` berdiri sendiri.
 *
 * Keputusan Iwan (1 Okt 2026): pembatalan ITO hanya boleh dilakukan oleh
 * user `marlov` (permission langsung ke user) dan role `admin` + `superadmin`.
 * Role lain (logistic, accounting, finance, logistics-only, ...) TIDAK boleh.
 *
 * Seeder ini idempoten: aman dijalankan berkali-kali, dan sekaligus MENCABUT
 * permission dari role/user yang tidak ada di daftar putih.
 */
class ItoCancelPermissionSeeder extends Seeder
{
    public const PERMISSION = 'cancel-ito';

    /** @var list<string> */
    public const ROLE_WHITELIST = ['admin', 'superadmin'];

    /** @var list<string> */
    public const USER_WHITELIST = ['marlov'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => self::PERMISSION]);

        $this->grantToWhitelistedRoles($permission);
        $this->grantToWhitelistedUsers($permission);
        $this->revokeFromOtherRoles($permission);
        $this->revokeFromOtherUsers($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->reportSummary($permission);
    }

    private function grantToWhitelistedRoles(Permission $permission): void
    {
        foreach (self::ROLE_WHITELIST as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null) {
                Log::warning('ItoCancelPermissionSeeder: role tidak ditemukan', ['role' => $roleName]);
                $this->command?->warn("Role {$roleName} tidak ditemukan — dilewati.");

                continue;
            }

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                $this->command?->info("Permission ".self::PERMISSION." diberikan ke role {$roleName}.");
            }
        }
    }

    private function grantToWhitelistedUsers(Permission $permission): void
    {
        foreach (self::USER_WHITELIST as $username) {
            $user = User::query()->where('username', $username)->first();

            if ($user === null) {
                Log::warning('ItoCancelPermissionSeeder: user tidak ditemukan', ['username' => $username]);
                $this->command?->warn("User {$username} tidak ditemukan — permission TIDAK diberikan. Periksa ejaan username.");

                continue;
            }

            if (! $user->hasDirectPermission($permission)) {
                $user->givePermissionTo($permission);
                $this->command?->info("Permission ".self::PERMISSION." diberikan langsung ke user {$username}.");
            }
        }
    }

    private function revokeFromOtherRoles(Permission $permission): void
    {
        foreach (Role::permission($permission)->get() as $role) {
            if (in_array($role->name, self::ROLE_WHITELIST, true)) {
                continue;
            }

            $role->revokePermissionTo($permission);
            $this->command?->line("Permission ".self::PERMISSION." dicabut dari role {$role->name}.");
        }
    }

    private function revokeFromOtherUsers(Permission $permission): void
    {
        foreach (User::permission($permission)->get() as $user) {
            if (in_array($user->username, self::USER_WHITELIST, true)) {
                continue;
            }

            // Hanya izin LANGSUNG yang dicabut di sini. Izin yang datang dari role
            // (mis. user admin/superadmin) bukan urusan seeder ini — dan mencetak
            // "dicabut" untuk mereka akan menyesatkan.
            if (! $user->hasDirectPermission($permission)) {
                continue;
            }

            $user->revokePermissionTo($permission);
            $this->command?->line("Permission ".self::PERMISSION." dicabut dari user {$user->username} (izin langsung).");
        }
    }

    private function reportSummary(Permission $permission): void
    {
        $roles = Role::permission($permission)->pluck('name')->sort()->values()->all();
        $users = User::permission($permission)->pluck('username')->sort()->values()->all();

        $message = 'Permission '.self::PERMISSION.' — role: '.(implode(', ', $roles) ?: '(tidak ada)')
            .' | user (termasuk lewat role): '.(implode(', ', $users) ?: '(tidak ada)');

        Log::info('ItoCancelPermissionSeeder: '.$message);
        $this->command?->info($message);
    }
}
