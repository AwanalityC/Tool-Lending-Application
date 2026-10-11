<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Logika bisnis peminjaman: pengajuan, persetujuan, penolakan, dan daftar.
 *
 * CATATAN TAHAP 4: persetujuan di sini memakai transaksi Laravel + penguncian baris.
 * Di Tahap 5 method setujui() diganti agar memanggil stored procedure sp_setujui_peminjaman,
 * dengan tes yang sama sebagai bukti hasilnya tidak berubah.
 */
class PeminjamanService
{
    /* ------------------------------------------------------------------ *
     * PENGAJUAN
     * ------------------------------------------------------------------ */

    public function ajukan(User $peminjam, array $data): Peminjaman
    {
        // Nomor transaksi dihitung dari nomor terakhir hari ini. Bila dua pengajuan bersamaan
        // mendapat nomor yang sama, UNIQUE di database menolak salah satunya, lalu kita ulang
        // (maksimal 3 kali) dengan nomor yang baru.
        $peminjaman = retry(
            3,
            fn () => DB::transaction(fn () => $this->simpanPengajuan($peminjam, $data)),
            50,
            fn (Throwable $e) => $e instanceof UniqueConstraintViolationException
        );

        return $peminjaman->load(Peminjaman::RELASI);
    }

    private function simpanPengajuan(User $peminjam, array $data): Peminjaman
    {
        $this->periksaStokSaatPengajuan($data['items']);

        $peminjaman = new Peminjaman(Arr::only($data, ['tanggal_pinjam', 'tanggal_jatuh_tempo', 'alasan']));
        $peminjaman->forceFill([
            'user_id' => $peminjam->id, // dari token login, bukan dari request
            'no_transaksi' => $this->nomorTransaksiBerikutnya(),
            'status' => Peminjaman::STATUS_MENUNGGU,
        ])->save();

        $peminjaman->detail()->createMany(array_map(
            fn (array $item) => [
                'alat_id' => (int) $item['alat_id'],
                'jumlah' => (int) $item['jumlah'],
            ],
            array_values($data['items'])
        ));

        // Pengajuan TIDAK mengubah stok. Stok baru berkurang saat disetujui.
        return $peminjaman;
    }

    /**
     * Pemeriksaan awal agar peminjam langsung tahu bila jumlah yang diminta mustahil dipenuhi.
     * Ini bukan jaminan stok: pemeriksaan yang menentukan dilakukan lagi (dengan penguncian)
     * saat pengajuan disetujui.
     */
    private function periksaStokSaatPengajuan(array $items): void
    {
        $alat = Alat::whereIn('id', array_column($items, 'alat_id'))->get()->keyBy('id');
        $errors = [];

        foreach ($items as $i => $item) {
            $a = $alat->get((int) $item['alat_id']);

            if (! $a || ! $a->aktif) {
                $errors["items.{$i}.alat_id"] = ['Alat tidak ditemukan atau sudah tidak aktif.'];
                continue;
            }

            if ((int) $item['jumlah'] > $a->stok_tersedia) {
                $errors["items.{$i}.jumlah"] = ["Stok {$a->nama} hanya tersedia {$a->stok_tersedia} unit."];
            }
        }

        if ($errors) {
            throw new ApiException('Jumlah alat melebihi stok yang tersedia.', 422, $errors);
        }
    }

    /** Format PJM-20261009-0001: tanggal hari ini + nomor urut harian. */
    private function nomorTransaksiBerikutnya(): string
    {
        $prefix = 'PJM-'.now()->format('Ymd').'-';

        $terakhir = Peminjaman::where('no_transaksi', 'like', $prefix.'%')->max('no_transaksi');
        $urut = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /* ------------------------------------------------------------------ *
     * PERSETUJUAN
     * ------------------------------------------------------------------ */

    /**
     * Menyetujui pengajuan dan mengurangi stok secara atomik.
     * Semua langkah dalam satu transaksi: bila ada yang gagal, tidak ada yang berubah.
     */
    public function setujui(Peminjaman $peminjaman, User $petugas): Peminjaman
    {
        // Stored procedure mengelola transaksinya sendiri; jangan bungkus dengan DB::transaction().
        try {
            DB::statement('CALL sp_setujui_peminjaman(?, ?)', [
                (int) $peminjaman->id,
                (int) $petugas->id,
            ]);
        } catch (QueryException $e) {
            $pesan = $e->getMessage();

            if (str_contains($pesan, 'PA:NOT_FOUND')) {
                abort(404, 'Pengajuan peminjaman tidak ditemukan.');
            }
            if (str_contains($pesan, 'PA:NOT_PENDING')) {
                throw ApiException::konflik('Pengajuan bukan lagi berstatus menunggu.');
            }
            if (str_contains($pesan, 'PA:STOCK_OR_INACTIVE')) {
                throw ApiException::konflik('Pengajuan tidak dapat disetujui karena stok tidak cukup atau alat tidak aktif.');
            }
            if (str_contains($pesan, 'PA:ITEMS_INVALID')) {
                throw ApiException::konflik('Rincian alat pada pengajuan tidak valid.');
            }

            throw $e;
        }

        return $peminjaman->fresh()->load(Peminjaman::RELASI);
    }

    /* ------------------------------------------------------------------ *
     * PENOLAKAN
     * ------------------------------------------------------------------ */

    /** Menolak pengajuan. Stok tidak disentuh karena belum pernah dikurangi. */
    public function tolak(Peminjaman $peminjaman, User $petugas, string $alasan): Peminjaman
    {
        $hasil = DB::transaction(function () use ($peminjaman, $petugas, $alasan) {
            $p = $this->kunciDanPastikanMenunggu($peminjaman, 'ditolak');

            $p->forceFill([
                'status' => Peminjaman::STATUS_DITOLAK,
                'alasan_penolakan' => $alasan,
                'diproses_oleh' => $petugas->id,
                'diproses_pada' => now(),
            ])->save();

            return $p;
        });

        return $hasil->load(Peminjaman::RELASI);
    }

    /** Mengunci baris pengajuan, membaca status terbaru, dan menolak bila bukan "menunggu". */
    private function kunciDanPastikanMenunggu(Peminjaman $peminjaman, string $tindakan): Peminjaman
    {
        $p = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

        if ($p->status !== Peminjaman::STATUS_MENUNGGU) {
            throw ApiException::konflik(
                "Pengajuan berstatus {$p->labelStatus()} sehingga tidak dapat {$tindakan}."
            );
        }

        return $p;
    }

    /* ------------------------------------------------------------------ *
     * DAFTAR
     * ------------------------------------------------------------------ */

    /**
     * Daftar peminjaman dengan filter. Jika $userId diisi, hasil dipaksa hanya milik pengguna itu
     * (dipakai untuk riwayat peminjam). Filter lain bersifat opsional.
     */
    public function daftar(array $filter, ?int $userId, int $perPage): LengthAwarePaginator
    {
        return Peminjaman::query()
            ->with(Peminjaman::RELASI)
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filter['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filter['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filter['q'] ?? null, function ($q, $kata) {
                $q->where(function ($w) use ($kata) {
                    $w->where('no_transaksi', 'like', "%{$kata}%")
                        ->orWhereHas('peminjam', fn ($u) => $u->where('nama', 'like', "%{$kata}%"));
                });
            })
            ->when($filter['tanggal_dari'] ?? null, fn ($q, $tgl) => $q->where('tanggal_pinjam', '>=', $tgl))
            ->when($filter['tanggal_sampai'] ?? null, fn ($q, $tgl) => $q->where('tanggal_pinjam', '<=', $tgl))
            // Terlambat = disetujui dan jatuh tempo sudah lewat (sama dengan Peminjaman::sedangTerlambat()).
            ->when(! empty($filter['terlambat']), fn ($q) => $q
                ->where('status', Peminjaman::STATUS_DISETUJUI)
                ->where('tanggal_jatuh_tempo', '<', today()->toDateString()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
