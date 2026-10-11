<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeminjamanRequest;
use App\Http\Requests\TolakPeminjamanRequest;
use App\Http\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PeminjamanController extends Controller
{
    use ApiResponse;

    public function __construct(private PeminjamanService $peminjamanService)
    {
    }

    /** GET /api/peminjaman : semua pengajuan (admin/petugas). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $this->peminjamanService->daftar(
            $this->validasiFilter($request),
            null,
            $this->perPage($request)
        );

        return PeminjamanResource::collection($data)
            ->additional(['sukses' => true, 'pesan' => 'Daftar peminjaman.']);
    }

    /** GET /api/riwayat-peminjaman : riwayat milik peminjam yang sedang login. */
    public function riwayat(Request $request): AnonymousResourceCollection
    {
        $filter = $this->validasiFilter($request);
        unset($filter['user_id']); // peminjam tidak boleh memfilter milik orang lain

        $data = $this->peminjamanService->daftar(
            $filter,
            $request->user()->id, // dipaksa dari token login
            $this->perPage($request)
        );

        return PeminjamanResource::collection($data)
            ->additional(['sukses' => true, 'pesan' => 'Riwayat peminjaman.']);
    }

    /** POST /api/peminjaman : peminjam mengajukan peminjaman. */
    public function store(StorePeminjamanRequest $request): JsonResponse
    {
        $peminjaman = $this->peminjamanService->ajukan($request->user(), $request->validated());

        return $this->sukses('Pengajuan peminjaman berhasil dikirim.', new PeminjamanResource($peminjaman), 201);
    }

    /** GET /api/peminjaman/{id} : pemilik atau staf. */
    public function show(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        // Bukan pemilik dan bukan staf: dijawab 404 (bukan 403) agar keberadaan data orang lain tidak bocor.
        if (! $request->user()->can('view', $peminjaman)) {
            abort(404);
        }

        return $this->sukses('Detail peminjaman.', new PeminjamanResource($peminjaman->load(Peminjaman::RELASI)));
    }

    /** POST /api/peminjaman/{id}/setujui */
    public function setujui(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $hasil = $this->peminjamanService->setujui($peminjaman, $request->user());

        return $this->sukses('Pengajuan disetujui dan stok alat telah dikurangi.', new PeminjamanResource($hasil));
    }

    /** POST /api/peminjaman/{id}/tolak */
    public function tolak(TolakPeminjamanRequest $request, Peminjaman $peminjaman): JsonResponse
    {
        $hasil = $this->peminjamanService->tolak(
            $peminjaman,
            $request->user(),
            $request->validated('alasan_penolakan')
        );

        return $this->sukses('Pengajuan ditolak.', new PeminjamanResource($hasil));
    }

    private function validasiFilter(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Peminjaman::LABEL_STATUS))],
            'q' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'terlambat' => ['nullable', 'boolean'], // kirim 1 atau 0
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 15), 1), 50);
    }
}
