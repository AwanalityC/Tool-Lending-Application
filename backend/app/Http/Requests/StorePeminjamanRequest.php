<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pembatasan peran (hanya peminjam) dilakukan oleh middleware pada route
    }

    // Field "user_id" sengaja tidak ada di sini. Pemilik pengajuan selalu diambil dari token login,
    // jadi peminjam tidak bisa mengajukan atas nama orang lain.
    public function rules(): array
    {
        return [
            'tanggal_pinjam' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tanggal_jatuh_tempo' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_pinjam'],
            'alasan' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            // distinct: satu alat hanya boleh muncul sekali dalam satu pengajuan.
            'items.*.alat_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('alat', 'id')->where(
                    fn ($q) => $q->where('aktif', true)->whereNull('deleted_at')
                ),
            ],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_pinjam.required' => 'Tanggal pinjam wajib diisi.',
            'tanggal_pinjam.date_format' => 'Format tanggal pinjam harus YYYY-MM-DD.',
            'tanggal_pinjam.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            'tanggal_jatuh_tempo.required' => 'Tanggal jatuh tempo wajib diisi.',
            'tanggal_jatuh_tempo.date_format' => 'Format tanggal jatuh tempo harus YYYY-MM-DD.',
            'tanggal_jatuh_tempo.after_or_equal' => 'Tanggal jatuh tempo tidak boleh sebelum tanggal pinjam.',
            'alasan.required' => 'Alasan peminjaman wajib diisi.',
            'alasan.max' => 'Alasan peminjaman maksimal 1000 karakter.',
            'items.required' => 'Pilih minimal satu alat.',
            'items.min' => 'Pilih minimal satu alat.',
            'items.max' => 'Satu pengajuan maksimal berisi 20 jenis alat.',
            'items.*.alat_id.required' => 'Alat wajib dipilih.',
            'items.*.alat_id.distinct' => 'Alat yang sama tidak boleh dipilih dua kali.',
            'items.*.alat_id.exists' => 'Alat tidak ditemukan atau sudah tidak aktif.',
            'items.*.jumlah.required' => 'Jumlah alat wajib diisi.',
            'items.*.jumlah.integer' => 'Jumlah alat harus berupa bilangan bulat.',
            'items.*.jumlah.min' => 'Jumlah alat minimal 1.',
        ];
    }
}
