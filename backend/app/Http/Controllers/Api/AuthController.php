<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        // Peran DIPAKSA menjadi peminjam. Klien tidak bisa menentukannya.
        $user = User::create([
            ...$request->validated(),
            'role' => User::ROLE_PEMINJAM,
            'aktif' => true,
        ]);

        return $this->sukses('Registrasi berhasil. Silakan login.', new UserResource($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Pesan sama untuk email salah dan password salah, supaya email tidak bisa ditebak.
        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            return $this->gagal('Email atau password salah.', 401);
        }

        if (!$user->aktif) {
            return $this->gagal('Akun Anda dinonaktifkan. Hubungi admin.', 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->sukses('Login berhasil.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Mencabut (menghapus) token yang sedang dipakai.
        $request->user()->currentAccessToken()->delete();

        return $this->sukses('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->sukses('Data pengguna.', new UserResource($request->user()));
    }
}