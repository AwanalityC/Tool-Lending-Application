<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PETUGAS = 'petugas';
    public const ROLE_PEMINJAM = 'peminjam';

    // PERHATIAN: "role" ada di $fillable, jadi jangan pernah memakai $request->all()
    // untuk membuat user. Selalu gunakan $request->validated() dan tetapkan role sendiri.
    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'jenis_peminjam',
        'nomor_induk',
        'no_hp',
        'aktif',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', // otomatis di-hash saat disimpan
            'aktif' => 'boolean',
        ];
    }
    /** Admin dan petugas dianggap "staf". */
    public function isStaf(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_PETUGAS], true);
    }
}