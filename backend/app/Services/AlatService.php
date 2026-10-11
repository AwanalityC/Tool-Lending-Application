<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Alat;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Semua perubahan stok alat dari sisi staf lewat sini.
 * Aturan: stok_total = stok_tersedia + stok_dipinjam + stok_rusak
 */
class AlatService
{
    private const FIELD_BIASA = ['kode', 'nama', 'kategori_id', 'deskripsi', 'kondisi', 'aktif'];

    public function buat(array $data): Alat
    {
        $rusak = $data['stok_rusak'] ?? 0;

        $alat = new Alat(Arr::only($data, self::FIELD_BIASA));
        $alat->aktif = $data['aktif'] ?? true;
        $alat->forceFill([
            'stok_total' => $data['stok_total'],
            'stok_rusak' => $rusak,
            'stok_dipinjam' => 0,
            'stok_tersedia' => $data['stok_total'] - $rusak,
        ])->save();

        return $alat->load('kategori:id,nama');
    }

    public function perbarui(Alat $alat, array $data): Alat
    {
        return DB::transaction(function () use ($alat, $data) {
            // Kunci baris supaya tidak bentrok dengan proses lain yang mengubah stok alat ini.
            $alat = Alat::whereKey($alat->id)->lockForUpdate()->firstOrFail();

            $total = $data['stok_total'];
            $rusak = $data['stok_rusak'] ?? $alat->stok_rusak;
            $tersedia = $total - $alat->stok_dipinjam - $rusak;

            if ($tersedia < 0) {
                throw ApiException::konflik(
                    "Stok total terlalu kecil: saat ini {$alat->stok_dipinjam} unit sedang dipinjam dan {$rusak} unit rusak.",
                    ['stok_total' => ['Stok total tidak boleh kurang dari unit yang dipinjam ditambah unit rusak.']]
                );
            }

            $alat->fill(Arr::only($data, self::FIELD_BIASA));
            $alat->forceFill([
                'stok_total' => $total,
                'stok_rusak' => $rusak,
                'stok_tersedia' => $tersedia,
            ])->save();

            return $alat->load('kategori:id,nama');
        });
    }

    public function hapus(Alat $alat): void
    {
        DB::transaction(function () use ($alat) {
            $alat = Alat::whereKey($alat->id)->lockForUpdate()->firstOrFail();

            // JOIN: cari transaksi yang masih berjalan untuk alat ini.
            $adaTransaksiAktif = DB::table('peminjaman_detail as d')
                ->join('peminjaman as p', 'p.id', '=', 'd.peminjaman_id')
                ->where('d.alat_id', $alat->id)
                ->whereIn('p.status', ['menunggu', 'disetujui'])
                ->exists();

            if ($adaTransaksiAktif || $alat->stok_dipinjam > 0) {
                throw ApiException::konflik(
                    'Alat tidak dapat dihapus karena masih memiliki transaksi peminjaman yang aktif.'
                );
            }

            $alat->delete(); // soft delete: riwayat transaksi lama tetap aman
        });
    }
}