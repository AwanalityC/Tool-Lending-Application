<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Peminjaman> */
class PeminjamanFactory extends Factory
{
    protected $model = Peminjaman::class;

    public function definition(): array
    {
        return [
            // Tanggal lampau agar tidak bentrok dengan nomor urut hari ini yang dibuat oleh API.
            'no_transaksi' => 'PJM-20200101-'.fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(3)->toDateString(),
            'alasan' => fake()->sentence(),
            'status' => Peminjaman::STATUS_MENUNGGU,
        ];
    }

    public function disetujui(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DISETUJUI,
            'diproses_oleh' => User::factory()->petugas(),
            'diproses_pada' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DITOLAK,
            'alasan_penolakan' => 'Alat sedang dipakai kegiatan sekolah.',
            'diproses_oleh' => User::factory()->petugas(),
            'diproses_pada' => now(),
        ]);
    }

    /** Disetujui dan jatuh temponya sudah lewat 2 hari. */
    public function terlambat(): static
    {
        return $this->disetujui()->state(fn () => [
            'tanggal_pinjam' => now()->subDays(5)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(2)->toDateString(),
        ]);
    }
}
