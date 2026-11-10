<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warga;
use App\Models\KategoriTarif;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserWargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Pastikan kategori tarif dasar tersedia
            $kategoriRumah = KategoriTarif::firstOrCreate(
                ['nama_kategori' => 'Rumah Tangga'],
                ['tarif_per_kubik' => 3000, 'biaya_pemeliharaan' => 10000]
            );

            $kategoriRuko = KategoriTarif::firstOrCreate(
                ['nama_kategori' => 'Ruko / Usaha'],
                ['tarif_per_kubik' => 5000, 'biaya_pemeliharaan' => 20000]
            );

            // 2. Buat Akun Petugas PAM
            User::updateOrCreate(
                ['email' => 'petugas@pam.test'],
                [
                    'name'     => 'Pak Slamet (Petugas Meter)',
                    'role'     => 'petugas',
                    'password' => Hash::make('password123'),
                ]
            );

            // 3. Data 3 Warga Percobaan
            $daftarWarga = [
                [
                    'nomor_rumah'       => 'A-12',
                    'nama_warga'        => 'Budi Santoso',
                    'nomor_telepon'     => '081234567890',
                    'kategori_tarif_id' => $kategoriRumah->id,
                ],
                [
                    'nomor_rumah'       => 'A-15',
                    'nama_warga'        => 'Siti Aminah',
                    'nomor_telepon'     => '081298765432',
                    'kategori_tarif_id' => $kategoriRumah->id,
                ],
                [
                    'nomor_rumah'       => 'B-01',
                    'nama_warga'        => 'Hendra Wijaya (Laundry Berkah)',
                    'nomor_telepon'     => '081355556666',
                    'kategori_tarif_id' => $kategoriRuko->id,
                ],
            ];

            // 4. Auto-Provisioning User & Warga (Sesuai Logika Opsi 1 WargaController)
            foreach ($daftarWarga as $item) {
                // Generate email otomatis dari nomor_rumah: "A-12" -> "a12@warga.test"
                $kodeRumah     = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item['nomor_rumah']));
                $emailOtomatis = $kodeRumah . '@warga.test';

                // Buat atau perbarui akun di tabel users (password default = nomor_telepon)
                $userWarga = User::updateOrCreate(
                    ['email' => $emailOtomatis],
                    [
                        'name'     => $item['nama_warga'],
                        'role'     => 'warga',
                        'password' => Hash::make($item['nomor_telepon']),
                    ]
                );

                // Hubungkan user_id ke tabel warga
                Warga::updateOrCreate(
                    ['nomor_rumah' => strtoupper($item['nomor_rumah'])],
                    [
                        'user_id'           => $userWarga->id, // <-- Terhubung ke ID tabel users
                        'kategori_tarif_id' => $item['kategori_tarif_id'],
                        'nama_warga'        => $item['nama_warga'],
                        'nomor_telepon'     => $item['nomor_telepon'],
                        'status_aktif'      => true,
                    ]
                );
            }
        });
    }
}
