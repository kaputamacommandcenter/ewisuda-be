<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Acara;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreAcaraRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcaraController extends Controller
{
    /**
     * Menampilkan daftar semua acara.
     */
    public function index()
    {
        try {
            $acaras = Acara::orderBy('id', 'desc')->get();
            return ApiResponse::success($acaras, 'Data acara berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Menyimpan acara baru ke database.
     */
    public function store(StoreAcaraRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle Upload Background Image
            if ($request->hasFile('background_image')) {
                $file = $request->file('background_image');
                $path = $file->store('backgrounds', 'public');
                $data['background_image'] = Storage::url($path);
            }

            // Generate slug dari kegiatan dan tahun_akademik
            $data['slug'] = Str::slug($data['kegiatan'] . ' ' . ($data['tahun_akademik'] ?? ''));

            $acara = Acara::create($data);
            return ApiResponse::success($acara, 'Acara baru berhasil dibuat.', 201);
        } catch (Exception $e) {
            return ApiResponse::error('Gagal membuat acara: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Menampilkan detail satu acara.
     */
    public function show(Acara $acara)
    {
        return ApiResponse::success($acara, 'Detail acara berhasil diambil.');
    }

    /**
     * Memperbarui data acara di database.
     */
    public function update(Request $request, Acara $acara)
    {
        try {
            // Validasi file jika ada upload background baru
            $request->validate([
                'background_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $data = $request->all();

            // Handle Update Upload Background Image
            if ($request->hasFile('background_image')) {
                // Hapus gambar lama dari storage jika berupa file lokal (tersimpan di public/backgrounds)
                if ($acara->background_image && str_contains($acara->background_image, 'storage/backgrounds/')) {
                    $oldPath = str_replace(Storage::url(''), '', $acara->background_image);
                    Storage::disk('public')->delete($oldPath);
                }

                // Simpan gambar baru
                $file = $request->file('background_image');
                $path = $file->store('backgrounds', 'public');
                $data['background_image'] = Storage::url($path);
            }

            // Ambil nilai kegiatan & tahun_akademik baru, atau gunakan nilai lama jika tidak di-input
            $kegiatan = $data['kegiatan'] ?? $acara->kegiatan;
            $tahunAkademik = $data['tahun_akademik'] ?? $acara->tahun_akademik;

            // Generate slug baru
            $data['slug'] = Str::slug($kegiatan . ' ' . $tahunAkademik);

            $acara->update($data);

            return ApiResponse::success($acara, 'Data acara dan undangan berhasil diperbarui.');
        } catch (Exception $e) {
            return ApiResponse::error('Gagal memperbarui data acara: ' . $e->getMessage(), 500);
        }
    }

    public function showBySlug($slug)
    {
        try {
            $acara = Acara::where('slug', $slug)->first();

            if (!$acara) {
                return ApiResponse::error('Data acara tidak ditemukan.', 404);
            }

            return ApiResponse::success($acara, 'Detail acara berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Menghapus acara dari database.
     */
    public function destroy(Acara $acara)
    {
        try {
            // Hapus gambar background dari storage jika ada
            if ($acara->background_image && str_contains($acara->background_image, 'storage/backgrounds/')) {
                $oldPath = str_replace(Storage::url(''), '', $acara->background_image);
                Storage::disk('public')->delete($oldPath);
            }

            $acara->delete();
            return ApiResponse::success(null, 'Acara berhasil dihapus.');
        } catch (Exception $e) {
            return ApiResponse::error('Gagal menghapus acara: ' . $e->getMessage(), 500);
        }
    }
}
