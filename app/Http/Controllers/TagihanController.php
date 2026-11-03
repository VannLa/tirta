<?php

namespace App\Http\Controllers;
use App\Models\Tagihan;
use App\Http\Resources\TagihanResource;


use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index()
    {
        $user  = $request->user();
        $tagihan = Tagihan::with(['warga', 'pencatatanMeter'])->latest()->get();
        // Data Scoping: Jika yang login adalah warga, filter hanya tagihan milik warga tersebut
        if ($user->role === 'warga') {
            $wargaId = $user->warga->id ?? 0;
            $query->where('warga_id', $wargaId);
        }

        return TagihanResource::collection($query->get());

    }
 
    public function bayar($id)
    {
        $user    = $request->user();
        $tagihan = Tagihan::findOrFail($id);

        if ($user->role === 'warga' && $tagihan->warga_id !== ($user->warga->id ?? 0)) {
            return response()->json([
                'message' => 'Anda tidak berhak mengakses tagihan milik warga lain.'
            ], 403);
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
            'data' => new TagihanResource($tagihan)
        ], 200);
    }

}
