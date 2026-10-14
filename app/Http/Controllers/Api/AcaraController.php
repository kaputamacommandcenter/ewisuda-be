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
            // Validasi unggahan berkas gambar background & berkas PDF buku wisuda
            $request->validate([
                'background_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Maks 5MB
                'file_buku_wisuda' => 'nullable|mimes:pdf|max:20480',                   // Maks 20MB
            ]);

            $data = $request->all();

            // 1. Handle Update / Replace Upload Background Image
            if ($request->hasFile('background_image')) {
                // Hapus gambar lama jika tersimpan di disk public
                if ($acara->background_image) {
                    $oldBgPath = str_replace('/storage/', '', parse_url($acara->background_image, PHP_URL_PATH));
                    if (Storage::disk('public')->exists($oldBgPath)) {
                        Storage::disk('public')->delete($oldBgPath);
                    }
                }

                // Simpan gambar baru
                $fileBg = $request->file('background_image');
                $pathBg = $fileBg->store('backgrounds', 'public');
                $data['background_image'] = Storage::url($pathBg);
            }

            // 2. Handle Update / Replace Upload File Buku Wisuda (PDF)
            if ($request->hasFile('file_buku_wisuda')) {
                // Hapus file PDF lama jika tersimpan di disk public
                if ($acara->file_buku_wisuda) {
                    $oldPdfPath = str_replace('/storage/', '', parse_url($acara->file_buku_wisuda, PHP_URL_PATH));
                    if (Storage::disk('public')->exists($oldPdfPath)) {
                        Storage::disk('public')->delete($oldPdfPath);
                    }
                }

                // Simpan berkas PDF baru
                $filePdf = $request->file('file_buku_wisuda');
                $pathPdf = $filePdf->store('buku-wisuda', 'public');
                $data['file_buku_wisuda'] = Storage::url($pathPdf);
            }

            // 3. Pemetaan alias kolom jika frontend mengirim parameter alternatif
            if (isset($data['tanggal'])) {
                $data['tanggal_wisuda'] = $data['tanggal'];
            }
            if (isset($data['lokasi'])) {
                $data['gedung_wisuda'] = $data['lokasi'];
            }

            // 4. Ambil nilai kegiatan & tahun_akademik baru, atau gunakan nilai lama jika tidak di-input
            $kegiatan = $data['kegiatan'] ?? $acara->kegiatan;
            $tahunAkademik = $data['tahun_akademik'] ?? $acara->tahun_akademik;

            // 5. Regenerate slug baru
            $data['slug'] = Str::slug($kegiatan . ' ' . $tahunAkademik);

            // Update data ke database
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
