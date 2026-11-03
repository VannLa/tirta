<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
   public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('warga')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial email atau kata sandi yang Anda masukkan tidak valid.'],
            ]);
        }

        // Hapus token lama di perangkat yang sama agar tabel token tetap bersih
        $user->tokens()->delete();

        // Terbitkan Personal Access Token dengan abilities sesuai role pengguna
        $token = $user->createToken('pam-auth-token', [$user->role])->plainTextToken;

        return response()->json([
            'message'      => 'Autentikasi berhasil.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'nomor_rumah' => $user->warga->nomor_rumah ?? null,
            ]
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('warga')
        ], 200);
    }

    public function logout(Request $request)
    {
        // Cabut (revoke) token yang sedang digunakan pada request saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout. Token akses telah dicabut dari server.'
        ], 200);
    }

}
