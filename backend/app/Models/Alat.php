<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alat';

    // Kolom stok_* SENGAJA tidak ada di sini. Stok hanya diubah oleh AlatService
    // (dan nanti prosedur peminjaman), tidak bisa diisi langsung dari request.
    protected $fillable = ['kode', 'nama', 'kategori_id', 'deskripsi', 'gambar', 'kondisi', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'stok_total' => 'integer',
            'stok_tersedia' => 'integer',
            'stok_dipinjam' => 'integer',
            'stok_rusak' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** Pencarian berdasarkan nama atau kode alat. */
    public function scopeCari(Builder $query, string $kata): Builder
    {
        return $query->where(function (Builder $w) use ($kata) {
            $w->where('nama', 'like', "%{$kata}%")
                ->orWhere('kode', 'like', "%{$kata}%");
        });
    }
}