<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('kode')) {
            $this->merge(['kode' => strtoupper(trim((string) $this->input('kode')))]);
        }
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('alat', 'kode')->ignore($this->route('alat'))],
            'nama' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kondisi' => ['required', 'in:baik,rusak_ringan,rusak_berat'],
            'stok_total' => ['required', 'integer', 'min:1', 'max:100000'],
            'stok_rusak' => ['nullable', 'integer', 'min:0', 'lte:stok_total'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreAlatRequest())->messages();
    }
}