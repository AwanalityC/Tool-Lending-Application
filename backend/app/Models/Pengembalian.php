<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengembalian extends Model
{
    protected $table = 'pengembalian';

    protected $fillable = [
        'peminjaman_id',
        'tanggal_kembali',
        'petugas_id',
        'hari_terlambat',
        'tarif_per_hari_saat_itu',
        'denda_terlambat',
        'denda_kerusakan',
        'total_denda',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'datetime',
            'hari_terlambat' => 'integer',
            'tarif_per_hari_saat_itu' => 'decimal:2',
            'denda_terlambat' => 'decimal:2',
            'denda_kerusakan' => 'decimal:2',
            'total_denda' => 'decimal:2',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
