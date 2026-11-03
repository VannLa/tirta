<?php

namespace App\Http\Controllers;
use App\Models\PencatatanMeter;
use App\Models\Warga;
use App\Models\Tagihan;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PencatatanMeterController extends Controller
{
        public function store(Request $request)
    {
        $validated = $request->validate([
            'warga_id'      => 'required|exists:wargas,id',
            'bulan_tahun'   => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // Format YYYY-MM
            'meter_awal'    => 'required|numeric|min:0',
            'meter_akhir'   => 'required|numeric|gte:meter_awal',
            'tanggal_catat' => 'required|date',
        ]);

        // 1. Pencegahan Idempotensi Dasar: Cek apakah warga sudah dicatat pada periode bulan_tahun ini
        $sudahTercatat = PencatatanMeter::where('warga_id', $validated['warga_id'])
            ->where('bulan_tahun', $validated['bulan_tahun'])
            ->exists();

        if ($sudahTercatat) {
            throw ValidationException::withMessages([
                'bulan_tahun' => ["Pencatatan meter untuk warga ini pada periode {$validated['bulan_tahun']} sudah pernah dilakukan."]
            ]);
        }

        // 2. Eksekusi Atomic menggunakan Database Transaction (All-or-Nothing)
        return DB::transaction(function () use ($validated) {
            $totalPemakaian = $validated['meter_akhir'] - $validated['meter_awal'];
            $validated['total_pemakaian'] = $totalPemakaian;

            // Operasi Tulis 1: Simpan pencatatan meter
            $pencatatan = PencatatanMeter::create($validated);

            // Ambil profil warga beserta rincian kategori tarifnya
            $warga = Warga::with('kategoriTarif')->findOrFail($validated['warga_id']);
            $tarifPerKubik = $warga->kategoriTarif->tarif_per_kubik;
            $biayaBeban    = $warga->kategoriTarif->biaya_pemeliharaan;

            $totalBiaya   = ($totalPemakaian * $tarifPerKubik) + $biayaBeban;
            $nomorTagihan = 'INV/' . str_replace('-', '', $validated['bulan_tahun']) . '/' . $warga->nomor_rumah;

            // Operasi Tulis 2: Terbitkan tagihan otomatis
            $tagihan = Tagihan::create([
                'pencatatan_meter_id' => $pencatatan->id,
                'warga_id'            => $warga->id,
                'nomor_tagihan'       => $nomorTagihan,
                'total_tagihan'       => $totalBiaya,
                'status_pembayaran'   => 'belum_bayar',
                'tanggal_tenggat'     => now()->addDays(15)->format('Y-m-d'),
            ]);

            return response()->json([
                'message'    => 'Pencatatan meter berhasil disimpan dan tagihan otomatis diterbitkan secara atomik.',
                'pencatatan' => $pencatatan,
                'tagihan'    => $tagihan
            ], 201);
        });
    }


}
