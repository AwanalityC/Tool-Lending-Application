<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin awal. Email dan password dibaca dari .env, bukan dari registrasi publik.
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@pinjamalat.test')],
            [
                'nama' => 'Administrator',
                'password' => env('ADMIN_PASSWORD', 'Admin#12345'),
                'role' => User::ROLE_ADMIN,
                'aktif' => true,
            ]
        );

        // Akun contoh hanya dibuat di lingkungan development/testing, tidak di produksi.
        if (app()->isProduction()) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'petugas@pinjamalat.test'],
            ['nama' => 'Petugas Satu', 'password' => 'Petugas#12345', 'role' => User::ROLE_PETUGAS, 'aktif' => true]
        );

        User::updateOrCreate(
            ['email' => 'siswa@pinjamalat.test'],
            [
                'nama' => 'Siswa Contoh',
                'password' => 'Siswa#12345',
                'role' => User::ROLE_PEMINJAM,
                'jenis_peminjam' => 'siswa',
                'nomor_induk' => '2024001',
                'aktif' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'guru@pinjamalat.test'],
            [
                'nama' => 'Guru Contoh',
                'password' => 'Guru#12345',
                'role' => User::ROLE_PEMINJAM,
                'jenis_peminjam' => 'guru',
                'nomor_induk' => '198501012010011001',
                'aktif' => true,
            ]
        );
    }
}