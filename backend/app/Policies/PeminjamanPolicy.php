<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

/**
 * Laravel menemukan policy ini otomatis karena namanya mengikuti model (Peminjaman -> PeminjamanPolicy).
 */
class PeminjamanPolicy
{
    /** Staf boleh melihat semua transaksi. Peminjam hanya boleh melihat miliknya sendiri. */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isStaf() || (int) $peminjaman->user_id === (int) $user->id;
    }
}
