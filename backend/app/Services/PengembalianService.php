<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PengembalianService
{
    /**
     * Proses pengembalian secara atomik: simpan rincian, kembalikan stok,
     * hitung denda, simpan header pengembalian, lalu ubah status menjadi selesai.
     */
    public function proses(Peminjaman $peminjaman, User $petugas, array $data): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman, $petugas, $data) {
            $p = Peminjaman::query()->whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== Peminjaman::STATUS_DISETUJUI) {
                throw ApiException::konflik('Pengembalian hanya dapat diproses untuk peminjaman yang disetujui dan belum selesai.');
            }

            if (Pengembalian::query()->where('peminjaman_id', $p->id)->exists()) {
                throw ApiException::konflik('Pengembalian untuk peminjaman ini sudah pernah dicatat.');
            }

            $details = PeminjamanDetail::query()
                ->where('peminjaman_id', $p->id)
                ->orderBy('alat_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = collect($data['items'])->keyBy(fn (array $item) => (int) $item['detail_id']);
            $expectedIds = $details->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
            $receivedIds = $items->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($expectedIds !== $receivedIds) {
                throw new ApiException(
                    'Rincian pengembalian harus mencakup semua jenis alat dalam peminjaman ini.',
                    422,
                    ['items' => ['Rincian tidak sesuai dengan data peminjaman.']]
                );
            }

            $pengaturan = DB::table('pengaturan_denda')->orderByDesc('id')->first();
            if (! $pengaturan) {
                throw ApiException::konflik('Pengaturan denda belum tersedia di database. Tambahkan pengaturan denda terlebih dahulu.');
            }

            $tarifTerlambat = (float) $pengaturan->tarif_terlambat_per_hari;
            $tarifRusakRingan = (float) $pengaturan->denda_rusak_ringan;
            $tarifRusakBerat = (float) $pengaturan->denda_rusak_berat;
            $totalDendaKerusakan = 0.0;
            $tanggalKembali = now();
            $tanggalJatuhTempo = $p->tanggal_jatuh_tempo->toDateString();
            $hariTerlambat = (int) (DB::selectOne(
                'SELECT GREATEST(DATEDIFF(?, ?), 0) AS hari_terlambat',
                [$tanggalKembali->toDateString(), $tanggalJatuhTempo]
            )->hari_terlambat ?? 0);

            foreach ($details as $detail) {
                $item = $items->get((int) $detail->id);
                $baik = (int) $item['jumlah_baik'];
                $rusakRingan = (int) $item['jumlah_rusak_ringan'];
                $rusakBerat = (int) $item['jumlah_rusak_berat'];
                $jumlahKembali = $baik + $rusakRingan + $rusakBerat;

                if ($jumlahKembali !== (int) $detail->jumlah) {
                    throw new ApiException(
                        "Pembagian kondisi untuk rincian #{$detail->id} harus berjumlah {$detail->jumlah} unit.",
                        422,
                        ['items' => ["Rincian #{$detail->id}: jumlah kondisi tidak sama dengan jumlah dipinjam."]]
                    );
                }

                $alat = Alat::withTrashed()->whereKey($detail->alat_id)->lockForUpdate()->first();
                if (! $alat || (int) $alat->stok_dipinjam < (int) $detail->jumlah) {
                    throw ApiException::konflik("Stok alat #{$detail->alat_id} tidak konsisten dengan jumlah yang sedang dipinjam.");
                }

                $dendaItem = round(($rusakRingan * $tarifRusakRingan) + ($rusakBerat * $tarifRusakBerat), 2);
                $totalDendaKerusakan += $dendaItem;

                $detail->forceFill([
                    'jumlah_baik' => $baik,
                    'jumlah_rusak_ringan' => $rusakRingan,
                    'jumlah_rusak_berat' => $rusakBerat,
                    'denda_kerusakan' => number_format($dendaItem, 2, '.', ''),
                ])->save();

                // Total stok tetap: unit baik kembali tersedia, sedangkan unit rusak masuk stok_rusak.
                $alat->forceFill([
                    'stok_dipinjam' => (int) $alat->stok_dipinjam - (int) $detail->jumlah,
                    'stok_tersedia' => (int) $alat->stok_tersedia + $baik,
                    'stok_rusak' => (int) $alat->stok_rusak + $rusakRingan + $rusakBerat,
                ])->save();
            }

            $hasilFunction = DB::selectOne(
                'SELECT fn_hitung_denda_terlambat(?, ?, ?) AS denda',
                [$tanggalJatuhTempo, $tanggalKembali->toDateString(), number_format($tarifTerlambat, 2, '.', '')]
            );
            $dendaTerlambat = (float) ($hasilFunction->denda ?? 0);
            $totalDendaKerusakan = round($totalDendaKerusakan, 2);
            $totalDenda = round($dendaTerlambat + $totalDendaKerusakan, 2);

            Pengembalian::query()->create([
                'peminjaman_id' => $p->id,
                'tanggal_kembali' => $tanggalKembali,
                'petugas_id' => $petugas->id,
                'hari_terlambat' => $hariTerlambat,
                'tarif_per_hari_saat_itu' => number_format($tarifTerlambat, 2, '.', ''),
                'denda_terlambat' => number_format($dendaTerlambat, 2, '.', ''),
                'denda_kerusakan' => number_format($totalDendaKerusakan, 2, '.', ''),
                'total_denda' => number_format($totalDenda, 2, '.', ''),
                'catatan' => $data['catatan'] ?? null,
            ]);

            // Trigger membaca variabel sesi ini untuk mencatat petugas yang memproses pengembalian.
            DB::statement('SET @pinjamalat_status_actor_id = ?', [$petugas->id]);
            try {
                $p->forceFill(['status' => Peminjaman::STATUS_SELESAI])->save();
            } finally {
                DB::statement('SET @pinjamalat_status_actor_id = NULL');
            }

            return $p->fresh()->load(Peminjaman::RELASI);
        }, 3);
    }
}
