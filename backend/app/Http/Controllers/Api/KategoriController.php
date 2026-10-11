<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKategoriRequest;
use App\Http\Requests\UpdateKategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KategoriController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $kategori = Kategori::query()
            ->withCount('alat')
            ->when($request->query('q'), fn($q, $kata) => $q->where('nama', 'like', "%{$kata}%"))
            ->orderBy('nama')
            ->paginate($perPage);

        return KategoriResource::collection($kategori)
            ->additional(['sukses' => true, 'pesan' => 'Daftar kategori.']);
    }

    public function show(Kategori $kategori): JsonResponse
    {
        $kategori->loadCount('alat');

        return $this->sukses('Detail kategori.', new KategoriResource($kategori));
    }

    public function store(StoreKategoriRequest $request): JsonResponse
    {
        $kategori = Kategori::create($request->validated());

        return $this->sukses('Kategori berhasil ditambahkan.', new KategoriResource($kategori), 201);
    }

    public function update(UpdateKategoriRequest $request, Kategori $kategori): JsonResponse
    {
        $kategori->update($request->validated());

        return $this->sukses('Kategori berhasil diperbarui.', new KategoriResource($kategori));
    }

    public function destroy(Kategori $kategori): JsonResponse
    {
        // withTrashed(): alat yang sudah di-soft-delete tetap memegang foreign key ke kategori ini.
        if ($kategori->alat()->withTrashed()->exists()) {
            throw ApiException::konflik('Kategori tidak dapat dihapus karena masih digunakan oleh alat.');
        }

        try {
            $kategori->delete();
        } catch (QueryException) {
            // Jaring pengaman: foreign key RESTRICT di database menolak bila ada yang lolos dari pengecekan.
            throw ApiException::konflik('Kategori tidak dapat dihapus karena masih digunakan.');
        }

        return $this->sukses('Kategori berhasil dihapus.');
    }
}