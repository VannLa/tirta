<?php

namespace App\Http\Controllers;
use App\Models\Warga;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class WargaController extends Controller
{
    public function index()
    {
        return response()->json(Warga::with('kategoriTarif')->get(), 200);
    }
 
    public function store(Request $request)
    {
       $validated = $request->validate([
        'kategori_tarif_id' => 'required|exists:kategori_tarif,id',
        'nomor_rumah'       => 'required|string|unique:warga,nomor_rumah',
        'nama_warga'        => 'required|string',
        'nomor_telepon'     => 'required|string',
    ]);

    return DB::transaction(function () use ($validated) {
        // 1. Generate email otomatis dari nomor rumah (contoh: "A-12" -> "a12@warga.test")
        $kodeRumah = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['nomor_rumah']));
        $emailOtomatis = $kodeRumah . '@warga.test';

        // 2. Buat akun login otomatis di tabel users (password default = nomor_telepon)
        $user = User::create([
            'name'     => $validated['nama_warga'],
            'email'    => $emailOtomatis,
            'role'     => 'warga',
            'password' => Hash::make($validated['nomor_telepon']),
        ]);

        // 3. Simpan profil warga dan ikat dengan user_id yang baru dibuat
        $warga = Warga::create([
            'user_id'           => $user->id,
            'kategori_tarif_id' => $validated['kategori_tarif_id'],
            'nomor_rumah'       => strtoupper($validated['nomor_rumah']),
            'nama_warga'        => $validated['nama_warga'],
            'nomor_telepon'     => $validated['nomor_telepon'],
            'status_aktif'      => true,
        ]);

        return response()->json([
            'message'    => 'Warga dan akun login berhasil dibuat otomatis.',
            'warga'      => $warga,
            'info_login' => [
                'email_login'      => $emailOtomatis,
                'password_default' => $validated['nomor_telepon'] . ' (Nomor Telepon)'
            ]
        ], 201);
    });
    }

}
