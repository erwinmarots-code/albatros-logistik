<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\ShippingProject;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        // ============================================
        // 1. BRANCHES (Hanya UPG dan JKT)
        // ============================================
        $upg = Branch::create([
            'code' => 'UPG',
            'name' => 'Cabang Makassar',
            'address' => 'Jl. Urip Sumoharjo No. 88',
            'phone' => '0411-123456',
        ]);

        $jkt = Branch::create([
            'code' => 'JKT',
            'name' => 'Cabang Jakarta',
            'address' => 'Jl. Sudirman No. 45',
            'phone' => '021-789012',
        ]);

        // ============================================
        // 2. USERS
        // ============================================
        // Super Admin – branch_id = null (bisa akses semua)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@albatros.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'branch_id' => null,
        ]);

        // Admin Makassar
        $adminUpg = User::create([
            'name' => 'Admin Makassar',
            'email' => 'admin.upg@albatros.com',
            'password' => Hash::make('password123'),
            'role' => 'branch_admin',
            'branch_id' => $upg->id,
        ]);

        // Admin Jakarta
        $adminJkt = User::create([
            'name' => 'Admin Jakarta',
            'email' => 'admin.jkt@albatros.com',
            'password' => Hash::make('password123'),
            'role' => 'branch_admin',
            'branch_id' => $jkt->id,
        ]);

        // ============================================
        // 3. CLIENTS (Hapus pic_name & npwp jika tidak ada di tabel)
        // ============================================
        $clientUpg = Client::create([
            'name' => 'PT. ABC Express',
            'email' => 'abc@abc.co.id',
            'phone' => '0411-411411',
            'address' => 'Jl. Ir. Soetami No. 12, Makassar',
            'branch_id' => $upg->id,
        ]);

        $clientJkt = Client::create([
            'name' => 'PT. XYZ Logistics',
            'email' => 'xyz@xyz.co.id',
            'phone' => '021-789123',
            'address' => 'Jl. Gatot Subroto No. 88, Jakarta',
            'branch_id' => $jkt->id,
        ]);

        // ============================================
        // 4. VEHICLES (Hapus fuel_type jika tidak ada)
        // ============================================
        Vehicle::create([
            'plate_number' => 'DD 1234 AB',
            'brand' => 'Daihatsu',
            'model' => 'Granmax',
            'year' => 2020,
            'status' => 'available',
        ]);

        Vehicle::create([
            'plate_number' => 'B 5678 CD',
            'brand' => 'Mitsubishi',
            'model' => 'Colt Diesel',
            'year' => 2021,
            'status' => 'available',
        ]);

        // ============================================
        // 5. DRIVERS
        // ============================================
        Driver::create([
            'name' => 'Adi R',
            'phone' => '085285285252',
            'license_number' => '12345678',
            'address' => 'Makassar',
            'status' => 'available',
        ]);

        Driver::create([
            'name' => 'Budi S',
            'phone' => '081234567890',
            'license_number' => '87654321',
            'address' => 'Jakarta',
            'status' => 'available',
        ]);

        // ============================================
        // 6. SHIPPING PROJECTS
        // ============================================
        ShippingProject::create([
            'client_id' => $clientUpg->id,
            'branch_id' => $upg->id,
            'no_po' => 'PO-UPG-001',
            'no_resi' => 'UPG-2608-0001',
            'sender_name' => 'PT. ABC Express',
            'sender_address' => 'Jl. Ir. Soetami No. 12, Makassar',
            'sender_phone' => '0411-411411',
            'receiver_name' => 'PT. Sejahtera',
            'receiver_address' => 'Jl. Sultan Hasanuddin No. 45, Makassar',
            'receiver_phone' => '0411-345678',
            'goods_description' => 'Alat kesehatan',
            'weight_kg' => 78,
            'collie' => 2,
            'volumetric' => '80x40x30',
            'goods_value' => 1000000,
            'shipping_method' => 'darat',
            'status' => 'confirmed',
            'notes' => 'Packing khusus',
            'created_by' => $adminUpg->id,
        ]);

        ShippingProject::create([
            'client_id' => $clientJkt->id,
            'branch_id' => $jkt->id,
            'no_po' => 'PO-JKT-001',
            'no_resi' => 'JKT-2608-0001',
            'sender_name' => 'PT. XYZ Logistics',
            'sender_address' => 'Jl. Gatot Subroto No. 88, Jakarta',
            'sender_phone' => '021-789123',
            'receiver_name' => 'PT. Maju Jaya',
            'receiver_address' => 'Jl. MH Thamrin No. 20, Jakarta',
            'receiver_phone' => '021-456789',
            'goods_description' => 'Elektronik',
            'weight_kg' => 120,
            'collie' => 5,
            'volumetric' => '100x60x40',
            'goods_value' => 5000000,
            'shipping_method' => 'udara',
            'status' => 'draft',
            'notes' => '',
            'created_by' => $adminJkt->id,
        ]);

        $this->command->info('✅ Master data seeding completed!');
    }
}