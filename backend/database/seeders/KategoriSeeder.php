<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Elektronik' => 'Laptop, kabel, dan perangkat elektronik lainnya',
            'Audio Visual' => 'Proyektor, mikrofon, dan perlengkapan presentasi',
            'Alat Praktik' => 'Peralatan praktik laboratorium dan bengkel',
            'Alat Olahraga' => 'Perlengkapan olahraga sekolah',
        ];

        foreach ($data as $nama => $deskripsi) {
            Kategori::updateOrCreate(['nama' => $nama], ['deskripsi' => $deskripsi]);
        }
    }
}