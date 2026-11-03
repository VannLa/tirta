<?php

namespace Tests\Feature;

use App\Models\KategoriTarif;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTarifTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_tarif_model_inserts_into_the_correct_table(): void
    {
        KategoriTarif::create([
            'nama_kategori' => 'Rumah Tangga',
            'tarif_per_kubik' => 3000,
            'biaya_pemeliharaan' => 10000,
        ]);

        $this->assertDatabaseHas('kategori_tarifs', [
            'nama_kategori' => 'Rumah Tangga',
            'tarif_per_kubik' => 3000,
            'biaya_pemeliharaan' => 10000,
        ]);
    }

    public function test_pencatatan_meter_accepts_valid_warga_id_against_wargas_table(): void
    {
        $kategori = KategoriTarif::create([
            'nama_kategori' => 'Rumah Tangga',
            'tarif_per_kubik' => 3000,
            'biaya_pemeliharaan' => 10000,
        ]);

        $warga = Warga::create([
            'kategori_tarif_id' => $kategori->id,
            'nomor_rumah' => 'A-001',
            'nama_warga' => 'Budi',
            'nomor_telepon' => '08123456789',
            'status_aktif' => true,
        ]);

        $response = $this->postJson('/api/pencatatan-meter', [
            'warga_id' => $warga->id,
            'bulan_tahun' => '2026-11',
            'meter_awal' => 100,
            'meter_akhir' => 150,
            'tanggal_catat' => '2026-11-03',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Pencatatan meter berhasil disimpan dan tagihan otomatis diterbitkan secara atomik.');
    }
}
