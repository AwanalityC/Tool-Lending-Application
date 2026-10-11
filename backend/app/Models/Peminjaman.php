<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_SELESAI = 'selesai';

    public const LABEL_STATUS = [
        self::STATUS_MENUNGGU => 'Menunggu',
        self::STATUS_DISETUJUI => 'Disetujui',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_SELESAI => 'Selesai',
    ];

    /**
     * Relasi yang selalu dimuat sekaligus (eager loading) saat data dikirim ke klien,
     * supaya tidak terjadi query berulang (N+1).
     */
    public const RELASI = [
        'peminjam:id,nama,email,jenis_peminjam,nomor_induk',
        'pemroses:id,nama',
        'detail.alat:id,kode,nama',
    ];

    // Hanya isian formulir yang boleh diisi massal. user_id, no_transaksi, status, dan
    // data keputusan SENGAJA tidak ada di sini: semuanya ditetapkan oleh PeminjamanService.
    protected $fillable = ['tanggal_pinjam', 'tanggal_jatuh_tempo', 'alasan'];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'diproses_pada' => 'datetime',
        ];
    }

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(PeminjamanDetail::class);
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    /**
     * "Terlambat" tidak disimpan di database. Nilainya dihitung setiap kali dibaca:
     * status Disetujui dan hari ini sudah melewati tanggal jatuh tempo.
     * Pada tanggal jatuh tempo itu sendiri, peminjaman belum dianggap terlambat.
     */
    public function sedangTerlambat(): bool
    {
        return $this->status === self::STATUS_DISETUJUI
            && today()->gt($this->tanggal_jatuh_tempo);
    }

    public function hariTerlambat(): int
    {
        if (!$this->sedangTerlambat()) {
            return 0;
        }

        return (int) abs(today()->diffInDays($this->tanggal_jatuh_tempo));
    }
    /** Sisa hari sampai jatuh tempo (negatif = sudah lewat). Hanya untuk peminjaman yang sedang berjalan. */
    public function sisaHari(): ?int
    {
        if ($this->status !== self::STATUS_DISETUJUI) {
            return null;
        }

        return (int) round(($this->tanggal_jatuh_tempo->getTimestamp() - today()->getTimestamp()) / 86400);
    }
}
