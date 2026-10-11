<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak akses petugas/admin dibatasi oleh middleware pada routes/api.php.
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.detail_id' => ['required', 'integer', 'distinct', 'min:1'],
            'items.*.jumlah_baik' => ['required', 'integer', 'min:0'],
            'items.*.jumlah_rusak_ringan' => ['required', 'integer', 'min:0'],
            'items.*.jumlah_rusak_berat' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Rincian pengembalian wajib diisi.',
            'items.min' => 'Minimal satu rincian alat harus dikembalikan.',
            'items.*.detail_id.required' => 'Rincian peminjaman wajib dipilih.',
            'items.*.detail_id.distinct' => 'Rincian alat tidak boleh dikirim berulang.',
            'items.*.jumlah_baik.required' => 'Jumlah alat baik wajib diisi.',
            'items.*.jumlah_rusak_ringan.required' => 'Jumlah alat rusak ringan wajib diisi.',
            'items.*.jumlah_rusak_berat.required' => 'Jumlah alat rusak berat wajib diisi.',
        ];
    }
}
