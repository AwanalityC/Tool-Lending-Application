<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Error aturan bisnis (misalnya stok tidak cukup, data masih dipakai).
 * Laravel otomatis memanggil render() sehingga klien menerima JSON yang rapi.
 */
class ApiException extends RuntimeException
{
    public function __construct(string $pesan, protected int $status = 409, protected mixed $errors = null)
    {
        parent::__construct($pesan);
    }

    public static function konflik(string $pesan, mixed $errors = null): self
    {
        return new self($pesan, 409, $errors);
    }

    public function render(): JsonResponse
    {
        $body = ['sukses' => false, 'pesan' => $this->getMessage()];
        if ($this->errors !== null) {
            $body['errors'] = $this->errors;
        }

        return response()->json($body, $this->status);
    }

    // Ini bukan bug sistem, jadi tidak perlu dicatat sebagai error di log.
    public function report(): void
    {
    }
}