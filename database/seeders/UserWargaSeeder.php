<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warga;
use App\Models\KategoriTarif;
use Illuminate\Support\Facades\Hash;


class UserWargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan kategori tarif dasar tersedia
        $kategoriRumah = KategoriTarif::firstOrCreate(
            ['nama_kategori' => 'Rumah Tangga'],
            ['tarif_per_kubik' => 3000, 'biaya_pemeliharaan' => 10000]
        );

        // 1. Buat Akun Petugas PAM
        User::updateOrCreate(
            ['email' => 'petugas@pam.test'],
            [
                'name'     => 'pak Slamet (Petugas Meter)',
                'role'     => 'petugas',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Buat Akun Login Warga (Budi Santoso)
        $userWarga = User::updateOrCreate(
            ['email' => 'budi@warga.test'],
            [
                'name'     => 'Budi Santoso',
                'role'     => 'warga',
                'password' => Hash::make('password123'),
            ]
        );

        // 3. Hubungkan Akun Budi Santoso dengan Data Warga Rumah A-12
        Warga::updateOrCreate(
            ['nomor_rumah' => 'A-12'],
            [
                'id'           => 4,
                'kategori_tarif_id' => $kategoriRumah->id,
                'nama_warga'        => 'Budi Santoso',
                'nomor_telepon'     => '081234567890',
                'status_aktif'      => true,
            ]
        );

    }
}
