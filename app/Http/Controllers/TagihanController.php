<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Http\Resources\TagihanResource;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    // Perbaikan 1: Tambahkan 'Request $request' ke dalam parameter fungsi
    public function index(Request $request)
    {
        // Perbaikan 2: Pastikan relasi 'warga' di-load agar $user->warga tidak error
        $user  = $request->user()->loadMissing('warga');
        
        // Perbaikan 3: Jangan panggil ->get() dulu! Biarkan sebagai Query Builder ($query)
        $query = Tagihan::with(['warga', 'pencatatanMeter'])->latest();

        // Data Scoping: Jika yang login adalah warga, filter hanya tagihan milik warga tersebut
        if ($user->role === 'warga') {
            // Gunakan optional() untuk mencegah error jika relasi warga masih kosong/null
            $wargaId = optional($user->warga)->id ?? 0;
            $query->where('warga_id', $wargaId);
        }

        // Eksekusi ->get() HANYA pada saat me-return Resource
        return TagihanResource::collection($query->get());
    }

    // Perbaikan 1: Tambahkan 'Request $request' ke dalam parameter fungsi bayar
    public function bayar(Request $request, $id)
    {
        $user    = $request->user()->loadMissing('warga');
        $tagihan = Tagihan::findOrFail($id);

        if ($user->role === 'warga') {
            $wargaId = optional($user->warga)->id ?? 0;
            
            // Cegah warga mengakses tagihan orang lain (BOLA Protection)
            if ($tagihan->warga_id !== $wargaId) {
                return response()->json([
                    'message' => 'Anda tidak berhak mengakses tagihan milik warga lain.'
                ], 403);
            }
        }

        if ($tagihan->status_pembayaran === 'lunas') {
            return response()->json(['message' => 'Tagihan sudah lunas sebelumnya.'], 400);
        }

        $tagihan->update([
            'status_pembayaran' => 'lunas',
            'tanggal_bayar'     => now(),
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil dikonfirmasi.',
            'data'    => new TagihanResource($tagihan)
        ], 200);
    }
}