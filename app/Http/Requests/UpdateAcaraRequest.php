<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ApiResponse;

class UpdateAcaraRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan untuk membuat request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // 'sometimes' berarti validasi hanya berjalan jika field tersebut ada di request.
        // Ini berguna untuk operasi PATCH/PUT di mana tidak semua field dikirim.
        return [
            'kampus'         => 'sometimes|required|string|max:200',
            'kegiatan'       => 'sometimes|in:WISUDA',
            'keterangan'     => 'nullable|string|max:200',
            'tahun_akademik' => 'sometimes|required|string|max:9',
            'lokasi'         => 'sometimes|required|string|max:100',
            'tanggal'        => 'sometimes|required|string|max:50',
            'is_active'      => 'sometimes|in:0,1',
        ];
    }

    /**
     * Handle jika validasi gagal.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('Data yang dikirim tidak valid', 422, $validator->errors())
        );
    }
}
