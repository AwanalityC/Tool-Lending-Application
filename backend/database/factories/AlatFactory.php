<?php

namespace Database\Factories;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alat> */
class AlatFactory extends Factory
{
    protected $model = Alat::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(1, 20);

        return [
            'kode' => strtoupper(fake()->unique()->bothify('ALT-####')),
            'nama' => ucfirst(fake()->unique()->words(2, true)),
            'kategori_id' => Kategori::factory(),
            'deskripsi' => fake()->sentence(),
            'kondisi' => 'baik',
            'aktif' => true,
            'stok_total' => $total,
            'stok_tersedia' => $total,
            'stok_dipinjam' => 0,
            'stok_rusak' => 0,
        ];
    }
}