<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlatRequest extends FormRequest
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
            'kode' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._-]+$/', 'unique:alat,kode'],
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
        return [
            'kode.required' => 'Kode alat wajib diisi.',
            'kode.regex' => 'Kode hanya boleh huruf, angka, titik, garis bawah, dan strip.',
            'kode.unique' => 'Kode alat sudah digunakan.',
            'nama.required' => 'Nama alat wajib diisi.',
            'kategori_id.required' => 'Kategori wajib dipilih.',
            'kategori_id.exists' => 'Kategori tidak ditemukan.',
            'kondisi.in' => 'Kondisi harus baik, rusak_ringan, atau rusak_berat.',
            'stok_total.required' => 'Jumlah stok wajib diisi.',
            'stok_total.integer' => 'Jumlah stok harus berupa angka bulat.',
            'stok_total.min' => 'Jumlah stok minimal 1.',
            'stok_rusak.lte' => 'Jumlah rusak tidak boleh melebihi stok total.',
        ];
    }
}