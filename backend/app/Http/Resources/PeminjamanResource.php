<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $terlambat = $this->sedangTerlambat();

        return [
            'id' => $this->id,
            'no_transaksi' => $this->no_transaksi,
            'peminjam' => $this->whenLoaded('peminjam', fn() => [
                'id' => $this->peminjam->id,
                'nama' => $this->peminjam->nama,
                'email' => $this->peminjam->email,
                'jenis_peminjam' => $this->peminjam->jenis_peminjam,
                'nomor_induk' => $this->peminjam->nomor_induk,
            ]),
            'tanggal_pengajuan' => $this->created_at?->toIso8601String(),
            'tanggal_pinjam' => $this->tanggal_pinjam?->toDateString(),
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo?->toDateString(),
            'alasan' => $this->alasan,
            'status' => $this->status,
            'status_label' => $this->labelStatus(),
            // Dihitung saat dibaca, tidak disimpan di database.
            'terlambat' => $terlambat,
            'hari_terlambat' => $this->hariTerlambat(),
            'sisa_hari' => $this->sisaHari(),
            'status_tampilan' => $terlambat ? 'Terlambat' : $this->labelStatus(),
            'alasan_penolakan' => $this->alasan_penolakan,
            'diproses_oleh' => $this->whenLoaded('pemroses', fn() => [
                'id' => $this->pemroses->id,
                'nama' => $this->pemroses->nama,
            ]),
            'diproses_pada' => $this->diproses_pada?->toIso8601String(),
            'total_item' => $this->whenLoaded('detail', fn() => (int) $this->detail->sum('jumlah')),
            'items' => $this->whenLoaded('detail', fn() => $this->detail->map(fn($d) => [
                'id' => $d->id,
                'alat_id' => $d->alat_id,
                'kode' => $d->alat?->kode,
                'nama' => $d->alat?->nama,
                'jumlah' => $d->jumlah,
            ])->values()),
        ];
    }
}
