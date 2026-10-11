<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanDendaSeeder extends Seeder
{
    public function run(): void
    {
        // Tarif disimpan di database (bukan di kode) dan bisa diubah admin nanti.
        if (DB::table('pengaturan_denda')->doesntExist()) {
            DB::table('pengaturan_denda')->insert([
                'tarif_terlambat_per_hari' => 2000,
                'denda_rusak_ringan' => 10000,
                'denda_rusak_berat' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}