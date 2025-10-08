<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use Exception;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if required roles exist
        $roles = ['owner', 'admin', 'kasir', 'karyawan', 'customers service'];
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                throw new Exception("Role '$roleName' not found. Please run RoleAndPermissionSeeder first.");
            }
        }

        // Create Owner
        $owner = User::updateOrCreate(
            ['email' => 'owner@fellieflorist.com'],
            [
                'name' => 'Owner Florist',
                'password' => Hash::make('Owner_fellieflorist2025@###'),
                'status' => 'active',
            ]
        );
        $owner->roles()->sync([Role::where('name', 'owner')->first()->id]);

        // Create Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@fellieflorist.com'],
            [
                'name' => 'Admin Florist',
                'password' => Hash::make('Admin_fellieflorist123@###'),
                'status' => 'active',
            ]
        );
        $admin->roles()->sync([Role::where('name', 'admin')->first()->id]);

        // Create Kasir
        $kasir = User::updateOrCreate(
            ['email' => 'kasir@fellieflorist.com'],
            [
                'name' => 'Kasir Florist',
                'password' => Hash::make('Kasir_fellieflorist123@###'),
                'status' => 'active',
            ]
        );
        $kasir->roles()->sync([Role::where('name', 'kasir')->first()->id]);

        // Create Staff
        $karyawan = User::updateOrCreate(
            ['email' => 'karyawan@fellieflorist.com'],
            [
                'name' => 'karyawan',
                'password' => Hash::make('Karyawan_fellieflorist'),
                'status' => 'active',
            ]
        );
        $karyawan->roles()->sync([Role::where('name', 'karyawan')->first()->id]);

        // Create Customers Service
        $cs = User::updateOrCreate(
            ['email' => 'cs@fellieflorist.com'],
            [
                'name' => 'Customers Service',
                'password' => Hash::make('cs_fellieflorist'),
                'status' => 'active',
            ]
        );
        $cs->roles()->sync([Role::where('name', 'customers service')->first()->id]);
    }
}
