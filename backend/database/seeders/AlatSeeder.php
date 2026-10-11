<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /** Menjalankan ulang seeder ini mengembalikan stok contoh ke nilai awal. */
    public function run(): void
    {
        // [kode, nama, kategori, deskripsi, stok_total, stok_rusak, kondisi]
        $data = [
            ['LPT-001', 'Laptop Asus Vivobook', 'Elektronik', 'Laptop untuk praktik pemrograman', 10, 0, 'baik'],
            ['KBL-001', 'Kabel HDMI 5 Meter', 'Elektronik', 'Kabel HDMI untuk proyektor dan monitor', 15, 0, 'baik'],
            ['PRJ-001', 'Proyektor Epson EB-X500', 'Audio Visual', 'Proyektor untuk presentasi kelas', 4, 0, 'baik'],
            ['MIC-001', 'Mikrofon Wireless', 'Audio Visual', 'Mikrofon nirkabel untuk acara sekolah', 3, 1, 'rusak_ringan'],
            ['TRP-001', 'Tripod Kamera', 'Audio Visual', 'Tripod aluminium untuk kamera dan HP', 5, 0, 'baik'],
            ['MLT-001', 'Multimeter Digital', 'Alat Praktik', 'Pengukur tegangan, arus, dan hambatan', 12, 0, 'baik'],
            ['SLD-001', 'Solder Listrik 40W', 'Alat Praktik', 'Solder untuk praktik elektronika', 6, 0, 'baik'],
            ['BLA-001', 'Bola Voli', 'Alat Olahraga', 'Bola voli standar pertandingan', 8, 0, 'baik'],
        ];

        // unguarded: seeder boleh mengisi kolom stok secara langsung.
        Alat::unguarded(function () use ($data) {
            foreach ($data as [$kode, $nama, $kategori, $deskripsi, $total, $rusak, $kondisi]) {
                Alat::updateOrCreate(['kode' => $kode], [
                    'nama' => $nama,
                    'kategori_id' => Kategori::where('nama', $kategori)->value('id'),
                    'deskripsi' => $deskripsi,
                    'kondisi' => $kondisi,
                    'aktif' => true,
                    'stok_total' => $total,
                    'stok_rusak' => $rusak,
                    'stok_dipinjam' => 0,
                    'stok_tersedia' => $total - $rusak,
                ]);
            }
        });
    }
}