<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengembalianRequest;
use App\Http\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use App\Services\PengembalianService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PengembalianController extends Controller
{
    use ApiResponse;

    public function __construct(private PengembalianService $pengembalianService)
    {
    }

    /** POST /api/peminjaman/{peminjaman}/pengembalian — khusus admin/petugas. */
    public function store(StorePengembalianRequest $request, Peminjaman $peminjaman): JsonResponse
    {
        $hasil = $this->pengembalianService->proses(
            $peminjaman,
            $request->user(),
            $request->validated()
        );

        return $this->sukses('Pengembalian berhasil diproses.', new PeminjamanResource($hasil));
    }
}
