<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'role' => $this->role,
            'jenis_peminjam' => $this->jenis_peminjam,
            'nomor_induk' => $this->nomor_induk,
            'no_hp' => $this->no_hp,
            'aktif' => $this->aktif,
        ];
    }
}