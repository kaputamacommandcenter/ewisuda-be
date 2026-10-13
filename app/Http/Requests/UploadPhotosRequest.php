<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Set ke true karena kita akan menangani otorisasi di level rute atau controller.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // 'photos' adalah key yang dikirim dari frontend (wajib ada dan harus berupa array).
            'photos'   => 'required|array|max:20', // Batasi maksimal 20 file sekali upload.

            // Validasi untuk setiap file di dalam array 'photos'.
            'photos.*' => [
                'required', // Setiap item dalam array tidak boleh kosong.
                'image',    // Pastikan setiap file adalah gambar (jpg, jpeg, png, bmp, gif, svg, atau webp).
                'max:10096', // Ukuran maksimal setiap file adalah 4096 KB atau 4MB.
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'photos.required' => 'Tidak ada file yang dipilih untuk diunggah.',
            'photos.array' => 'Data foto harus dalam format array.',
            'photos.max' => 'Anda hanya bisa mengunggah maksimal 20 foto sekaligus.',
            'photos.*.required' => 'Salah satu file yang Anda pilih kosong atau rusak.',
            'photos.*.image' => 'Semua file yang diunggah harus berformat gambar.',
            'photos.*.max' => 'Ukuran salah satu file melebihi batas maksimal 10MB.',
        ];
    }
}

