<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlatRequest;
use App\Http\Requests\UpdateAlatRequest;
use App\Http\Resources\AlatResource;
use App\Models\Alat;
use App\Services\AlatService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlatController extends Controller
{
    use ApiResponse;

    public function __construct(private AlatService $alatService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $staf = $request->user()->isStaf();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);

        $alat = Alat::query()
            ->with('kategori:id,nama') // eager loading: hindari query berulang (N+1)
            // Peminjam hanya boleh melihat alat aktif. Dipaksa di server, bukan di Flutter.
            ->when(!$staf, fn($q) => $q->where('aktif', true))
            ->when($staf && $request->has('aktif'), fn($q) => $q->where('aktif', $request->boolean('aktif')))
            ->when($request->query('q'), fn($q, $kata) => $q->cari($kata))
            ->when($request->query('kategori_id'), fn($q, $id) => $q->where('kategori_id', $id))
            ->when($request->query('kondisi'), fn($q, $kondisi) => $q->where('kondisi', $kondisi))
            ->when($request->boolean('tersedia'), fn($q) => $q->where('stok_tersedia', '>', 0))
            ->orderBy('nama')
            ->paginate($perPage);

        return AlatResource::collection($alat)
            ->additional(['sukses' => true, 'pesan' => 'Daftar alat.']);
    }

    public function show(Request $request, Alat $alat): JsonResponse
    {
        // Peminjam tidak boleh tahu adanya alat nonaktif: tampil sebagai "tidak ditemukan".
        if (!$request->user()->isStaf() && !$alat->aktif) {
            abort(404);
        }

        return $this->sukses('Detail alat.', new AlatResource($alat->load('kategori:id,nama')));
    }

    public function store(StoreAlatRequest $request): JsonResponse
    {
        $alat = $this->alatService->buat($request->validated());

        return $this->sukses('Alat berhasil ditambahkan.', new AlatResource($alat), 201);
    }

    public function update(UpdateAlatRequest $request, Alat $alat): JsonResponse
    {
        $alat = $this->alatService->perbarui($alat, $request->validated());

        return $this->sukses('Alat berhasil diperbarui.', new AlatResource($alat));
    }

    public function destroy(Alat $alat): JsonResponse
    {
        $this->alatService->hapus($alat);

        return $this->sukses('Alat berhasil dihapus.');
    }
}