<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenggunaRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PenggunaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->query('role'), fn($q, $role) => $q->where('role', $role))
            ->when($request->query('q'), function ($q, $kata) {
                $q->where(fn($w) => $w->where('nama', 'like', "%{$kata}%")
                    ->orWhere('email', 'like', "%{$kata}%"));
            })
            ->orderBy('nama')
            ->paginate(15);

        return UserResource::collection($users)
            ->additional(['sukses' => true, 'pesan' => 'Daftar pengguna.']);
    }

    public function store(StorePenggunaRequest $request): JsonResponse
    {
        $user = User::create([...$request->validated(), 'aktif' => true]);

        return $this->sukses('Akun berhasil dibuat.', new UserResource($user), 201);
    }
}