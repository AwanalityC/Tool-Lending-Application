<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // peran dibatasi oleh middleware pada route
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', 'unique:kategori,nama'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique' => 'Nama kategori sudah ada.',
            'nama.max' => 'Nama kategori maksimal 100 karakter.',
        ];
    }
}