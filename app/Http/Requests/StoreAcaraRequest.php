<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kegiatan'            => 'required|string|max:255',
            'kampus'              => 'required|string|max:255',
            'keterangan'          => 'nullable|string',
            'tahun_akademik'      => 'nullable|string|max:50',

            // Dibuat nullable agar tidak error saat penambahan awal dari CreateEventView
            'tanggal_wisuda'      => 'nullable|date',
            'gedung_wisuda'       => 'nullable|string|max:255',
            'is_active'           => 'nullable|boolean',
        ];
    }
}
