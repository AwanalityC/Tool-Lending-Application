<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $staf = $request->user()?->isStaf() ?? false;

        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kategori_id' => $this->kategori_id,
            'kategori' => $this->whenLoaded('kategori', fn() => [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
            ]),
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar,
            'stok_total' => $this->stok_total,
            'stok_tersedia' => $this->stok_tersedia,
            // Rincian internal hanya untuk admin/petugas.
            'stok_dipinjam' => $this->when($staf, $this->stok_dipinjam),
            'stok_rusak' => $this->when($staf, $this->stok_rusak),
            'kondisi' => $this->kondisi,
            'kondisi_label' => match ($this->kondisi) {
                'baik' => 'Baik',
                'rusak_ringan' => 'Rusak Ringan',
                'rusak_berat' => 'Rusak Berat',
                default => $this->kondisi,
            },
            'aktif' => $this->aktif,
            'status_ketersediaan' => !$this->aktif
                ? 'Tidak aktif'
                : ($this->stok_tersedia > 0 ? 'Tersedia' : 'Habis'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}