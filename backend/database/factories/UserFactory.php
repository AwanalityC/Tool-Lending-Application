<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password', // di-hash oleh cast pada model
            'role' => User::ROLE_PEMINJAM,
            'jenis_peminjam' => 'siswa',
            'aktif' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => User::ROLE_ADMIN, 'jenis_peminjam' => null]);
    }

    public function petugas(): static
    {
        return $this->state(fn() => ['role' => User::ROLE_PETUGAS, 'jenis_peminjam' => null]);
    }
}