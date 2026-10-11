<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PengaturanDendaSeeder::class,
        ]);

        // Data contoh hanya untuk development, tidak untuk produksi.
        if (!app()->isProduction()) {
            $this->call([
                KategoriSeeder::class,
                AlatSeeder::class,
            ]);
        }
    }
}