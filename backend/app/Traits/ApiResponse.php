<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/** Pembentuk respons JSON yang seragam di seluruh controller. */
trait ApiResponse
{
    protected function sukses(string $pesan, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['sukses' => true, 'pesan' => $pesan, 'data' => $data], $status);
    }

    protected function gagal(string $pesan, int $status = 400, mixed $errors = null): JsonResponse
    {
        $body = ['sukses' => false, 'pesan' => $pesan];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}