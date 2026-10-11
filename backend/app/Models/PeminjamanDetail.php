<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu jenis alat dalam satu transaksi peminjaman.
 * Tabel ini adalah penghubung many-to-many antara peminjaman dan alat.
 */
class PeminjamanDetail extends Model
{
    protected $table = 'peminjaman_detail';

    // Kolom rincian pengembalian (jumlah_baik, dst.) baru diisi di Tahap 5 oleh service pengembalian.
    protected $fillable = ['alat_id', 'jumlah'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'jumlah_baik' => 'integer',
            'jumlah_rusak_ringan' => 'integer',
            'jumlah_rusak_berat' => 'integer',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    /** withTrashed: alat yang sudah di-soft-delete tetap tampil pada riwayat lama. */
    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class)->withTrashed();
    }
}
