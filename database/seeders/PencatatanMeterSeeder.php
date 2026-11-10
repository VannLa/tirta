<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warga;
use App\Models\PencatatanMeter;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PencatatanMeterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Skenario data historis 2 bulan (September Lunas, Oktober Belum Bayar) untuk 3 Warga
            $dataRiwayatMeter = [
                // === 1. Rumah A-12 (Budi Santoso - Rumah Tangga: Rp 3.000/m3 + Beban Rp 10.000) ===
                [
                    'nomor_rumah'       => 'A-12',
                    'bulan_tahun'       => '2026-09',
                    'meter_awal'        => 80,
                    'meter_akhir'       => 100, // Pakai: 20 m3 -> Tagihan: Rp 70.000
                    'tanggal_catat'     => '2026-09-25',
                    'status_pembayaran' => 'lunas',
                    'tanggal_bayar'     => '2026-10-02 10:15:00',
                ],
                [
                    'nomor_rumah'       => 'A-12',
                    'bulan_tahun'       => '2026-10',
                    'meter_awal'        => 100,
                    'meter_akhir'       => 125, // Pakai: 25 m3 -> Tagihan: Rp 85.000
                    'tanggal_catat'     => '2026-10-25',
                    'status_pembayaran' => 'belum_bayar',
                    'tanggal_bayar'     => null,
                ],

                // === 2. Rumah A-15 (Siti Aminah - Rumah Tangga: Rp 3.000/m3 + Beban Rp 10.000) ===
                [
                    'nomor_rumah'       => 'A-15',
                    'bulan_tahun'       => '2026-09',
                    'meter_awal'        => 40,
                    'meter_akhir'       => 55, // Pakai: 15 m3 -> Tagihan: Rp 55.000
                    'tanggal_catat'     => '2026-09-25',
                    'status_pembayaran' => 'lunas',
                    'tanggal_bayar'     => '2026-10-05 14:30:00',
                ],
                [
                    'nomor_rumah'       => 'A-15',
                    'bulan_tahun'       => '2026-10',
                    'meter_awal'        => 55,
                    'meter_akhir'       => 73, // Pakai: 18 m3 -> Tagihan: Rp 64.000
                    'tanggal_catat'     => '2026-10-25',
                    'status_pembayaran' => 'belum_bayar',
                    'tanggal_bayar'     => null,
                ],

                // === 3. Ruko B-01 (Hendra Laundry - Ruko/Usaha: Rp 5.000/m3 + Beban Rp 20.000) ===
                [
                    'nomor_rumah'       => 'B-01',
                    'bulan_tahun'       => '2026-09',
                    'meter_awal'        => 210,
                    'meter_akhir'       => 260, // Pakai: 50 m3 -> Tagihan: Rp 270.000
                    'tanggal_catat'     => '2026-09-25',
                    'status_pembayaran' => 'lunas',
                    'tanggal_bayar'     => '2026-10-01 09:00:00',
                ],
                [
                    'nomor_rumah'       => 'B-01',
                    'bulan_tahun'       => '2026-10',
                    'meter_awal'        => 260,
                    'meter_akhir'       => 320, // Pakai: 60 m3 -> Tagihan: Rp 320.000
                    'tanggal_catat'     => '2026-10-25',
                    'status_pembayaran' => 'belum_bayar',
                    'tanggal_bayar'     => null,
                ],
            ];

            foreach ($dataRiwayatMeter as $item) {
                // Cari warga beserta relasi kategori tarifnya
                $warga = Warga::with('kategoriTarif')
                    ->where('nomor_rumah', $item['nomor_rumah'])
                    ->first();

                // Lewati jika warga belum ada (pastikan UserWargaSeeder dijalankan lebih dulu)
                if (! $warga) {
                    continue;
                }

                $totalPemakaian = $item['meter_akhir'] - $item['meter_awal'];

                // 1. Simpan atau perbarui Pencatatan Meter (Idempoten berdasarkan warga_id & bulan_tahun)
                $pencatatan = PencatatanMeter::updateOrCreate(
                    [
                        'warga_id'    => $warga->id,
                        'bulan_tahun' => $item['bulan_tahun'],
                    ],
                    [
                        'meter_awal'      => $item['meter_awal'],
                        'meter_akhir'     => $item['meter_akhir'],
                        'total_pemakaian' => $totalPemakaian,
                        'tanggal_catat'   => $item['tanggal_catat'],
                    ]
                );

                // 2. Hitung Total Tagihan berdasarkan Kategori Tarif Warga
                $tarifPerKubik = $warga->kategoriTarif->tarif_per_kubik;
                $biayaBeban    = $warga->kategoriTarif->biaya_pemeliharaan;
                $totalBiaya    = ($totalPemakaian * $tarifPerKubik) + $biayaBeban;

                $nomorTagihan   = 'INV/' . str_replace('-', '', $item['bulan_tahun']) . '/' . $warga->nomor_rumah;
                $tanggalTenggat = Carbon::parse($item['tanggal_catat'])->addDays(15)->format('Y-m-d');

                // 3. Terbitkan atau perbarui Tagihan Otomatis
                Tagihan::updateOrCreate(
                    [
                        'nomor_tagihan' => $nomorTagihan,
                    ],
                    [
                        'pencatatan_meter_id' => $pencatatan->id,
                        'warga_id'            => $warga->id,
                        'total_tagihan'       => $totalBiaya,
                        'status_pembayaran'   => $item['status_pembayaran'],
                        'tanggal_tenggat'     => $tanggalTenggat,
                        'tanggal_bayar'       => $item['tanggal_bayar'],
                    ]
                );
            }
        });
    }
}