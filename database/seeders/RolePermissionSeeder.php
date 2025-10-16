<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.update', 
            'users.delete',
            
            // Gudang Management
            'gudang.view',
            'gudang.create',
            'gudang.update',
            'gudang.delete',
            
            // Area Gudang Management
            'area-gudang.view',
            'area-gudang.create',
            'area-gudang.update',
            'area-gudang.delete',
            
            // Kategori Barang Management
            'kategori-barang.view',
            'kategori-barang.create',
            'kategori-barang.update',
            'kategori-barang.delete',
            
            // Barang Management
            'barang.view',
            'barang.create',
            'barang.update',
            'barang.delete',
            'barang.scan',
            
            // Penempatan Barang Management
            'penempatan-barang.view',
            'penempatan-barang.create',
            'penempatan-barang.update',
            'penempatan-barang.delete',
            
            // Optimasi
            'optimasi.view',
            'optimasi.run',
            'optimasi.history',
            
            // Rekomendasi
            'rekomendasi.view',
            'rekomendasi.approve',
            'rekomendasi.reject',
            'rekomendasi.implement',
            
            // Laporan & Analytics
            'laporan.view',
            'analytics.view',
            'dashboard.view',
            
            // Log Aktivitas
            'log-aktivitas.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $leaderPaket = Role::firstOrCreate(['name' => 'leader-paket']);
        $gudang = Role::firstOrCreate(['name' => 'gudang']);

        // Assign Permissions to Roles
        
        // Supervisor (Super Admin) - All Permissions
        $supervisor->givePermissionTo(Permission::all());

        // Leader Paket - Management permissions
        $leaderPaket->givePermissionTo([
            'gudang.view',
            'area-gudang.view',
            'area-gudang.create',
            'area-gudang.update',
            'kategori-barang.view',
            'kategori-barang.create',
            'kategori-barang.update',
            'barang.view',
            'barang.create',
            'barang.update',
            'barang.scan',
            'penempatan-barang.view',
            'penempatan-barang.create',
            'penempatan-barang.update',
            'optimasi.view',
            'optimasi.run',
            'rekomendasi.view',
            'rekomendasi.approve',
            'rekomendasi.reject',
            'laporan.view',
            'analytics.view',
            'dashboard.view',
            'log-aktivitas.view',
        ]);

        // Gudang (Staff) - Basic operations
        $gudang->givePermissionTo([
            'area-gudang.view',
            'kategori-barang.view',
            'barang.view',
            'barang.scan',
            'penempatan-barang.view',
            'penempatan-barang.create',
            'penempatan-barang.update',
            'rekomendasi.view',
            'rekomendasi.implement',
            'dashboard.view',
        ]);

        // Create Sample Users
        $superAdmin = User::firstOrCreate(
            ['email' => 'supervisor@ncs.com'],
            [
                'nama' => 'Supervisor NCS',
                'password' => Hash::make('password123'),
                'nomor_telepon' => '081234567890',
                'aktif' => true,
            ]
        );
        $superAdmin->assignRole('supervisor');

        $leader = User::firstOrCreate(
            ['email' => 'leader@ncs.com'],
            [
                'nama' => 'Leader Paket',
                'password' => Hash::make('password123'),
                'nomor_telepon' => '081234567891',
                'aktif' => true,
            ]
        );
        $leader->assignRole('leader-paket');

        $staffGudang = User::firstOrCreate(
            ['email' => 'gudang@ncs.com'],
            [
                'nama' => 'Staff Gudang',
                'password' => Hash::make('password123'),
                'nomor_telepon' => '081234567892',
                'aktif' => true,
            ]
        );
        $staffGudang->assignRole('gudang');

        $this->command->info('Roles, Permissions, and Users created successfully!');
    }
}
