<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TolakPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pembatasan peran (admin/petugas) dilakukan oleh middleware pada route
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}
