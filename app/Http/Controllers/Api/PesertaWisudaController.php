<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesertaWisuda;
use App\Helpers\ApiResponse;
use App\Http\Requests\StorePesertaWisudaRequest;
use App\Http\Requests\UpdatePesertaWisudaRequest;
use App\Http\Requests\UploadPhotosRequest;
use App\Imports\PesertaWisudaImport;
use App\Models\Acara;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;

class PesertaWisudaController extends Controller
{
    /**
     * Menampilkan daftar semua peserta wisuda.
     */
    public function index(Request $request)
    {
        $prodi = $request->prodi;
        try {
            // Mengambil data peserta beserta relasi acara-nya
            $pesertaCollection = PesertaWisuda::where('prodi', $prodi)->with('acara')->orderBy('nama', 'asc')->get();
            $pesertaCollection->each(function ($peserta) {
                $peserta->photo = $this->getPhotoUrl($peserta->npm);
            });

            return ApiResponse::success($pesertaCollection, 'Data peserta wisuda berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    public function exportWord(Request $request, $eventId)
    {
        $prodiFilter = $request->query('prodi'); // Ambil filter prodi dari query string

        // Build query
        $query = PesertaWisuda::where('acara_id', $eventId);

        if ($prodiFilter) {
            $query->where('prodi', $prodiFilter);
        }

        $pesertaList = $query->orderBy('prodi')
            ->orderBy('npm')
            ->get();

        if ($pesertaList->isEmpty()) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $templatePath = storage_path('app/public/templates/template_peserta.docx');

        if (!file_exists($templatePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File template_peserta.docx tidak ditemukan di storage/app/public/templates/'
            ], 500);
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Duplikasi block peserta
        $templateProcessor->cloneBlock('block_peserta', count($pesertaList), true, true);

        foreach ($pesertaList as $index => $peserta) {
            $i = $index + 1;

            $templateProcessor->setValue("npm#{$i}", $peserta->npm ?? '-');
            $templateProcessor->setValue("nama#{$i}", $peserta->nama ?? '-');
            $templateProcessor->setValue("no_urut#{$i}", str_pad($i, 3, '0', STR_PAD_LEFT));
            $templateProcessor->setValue("tempat_lahir#{$i}", $peserta->tempat_lahir ?? '-');
            $templateProcessor->setValue("tanggal_lahir#{$i}", $peserta->tanggal_lahir ?? '-');
            $templateProcessor->setValue("telp#{$i}", $peserta->telp ?? '-');
            $templateProcessor->setValue("jenjang#{$i}", $peserta->jenjang_pendidikan ?? '-');
            $templateProcessor->setValue("prodi#{$i}", $peserta->prodi ?? '-');
            $templateProcessor->setValue("alamat#{$i}", $peserta->alamat ?? '-');
            $templateProcessor->setValue("nama_ayah#{$i}", $peserta->nama_ayah ?? '-');
            $templateProcessor->setValue("nama_ibu#{$i}", $peserta->nama_ibu ?? '-');
            $templateProcessor->setValue("ipk#{$i}", $peserta->ipk ?? '-');
            $templateProcessor->setValue("judul#{$i}", $peserta->judul ?? '-');

            // Foto peserta
            $photoPath = public_path($peserta->photo);
            if (!file_exists($photoPath) || is_null($peserta->photo)) {
                $photoPath = public_path('default.png');
            }

            if (file_exists($photoPath)) {
                $templateProcessor->setImageValue("foto#{$i}", [
                    'path' => $photoPath,
                    'width' => 160,    // Tambah lebar foto (misal: 150 - 160)
                    'height' => 210,   // Tambah tinggi foto (misal: 200 - 210)
                    'ratio' => false
                ]);
            } else {
                $templateProcessor->setValue("foto#{$i}", '[Foto tidak ditemukan]');
            }
        }

        // Penamaan file dinamis berdasarkan prodi
        $safeProdiName = $prodiFilter ? '_' . str_replace(' ', '_', $prodiFilter) : '_Semua_Prodi';
        $fileName = "Daftar_Peserta_Wisuda{$safeProdiName}.docx";
        $tempPath = storage_path("app/public/{$fileName}");
        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Menyimpan peserta wisuda baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'acara_id' => 'required|exists:acaras,id',
            'npm' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'telp' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'jenjang_pendidikan' => 'nullable|string|max:50',
            'prodi' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'nama_ayah' => 'nullable|string|max:255',
            'nama_ibu' => 'nullable|string|max:255',
            'ipk' => 'nullable|numeric|between:0,4.00',
            'judul' => 'nullable|string',
            'tahun_lulus' => 'nullable|string|max:10',
        ]);

        // 2. Handle Upload Foto (jika ada)
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('peserta-photos', 'public');
            $photoUrl = Storage::url($path);
        }

        // 3. Simpan Data Peserta ke Database
        $peserta = PesertaWisuda::create([
            'acara_id' => $request->acara_id,
            'npm' => trim($request->npm),
            'nama' => trim($request->nama),
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'telp' => $request->telp,
            'photo' => $photoUrl,
            'jenjang_pendidikan' => $request->jenjang_pendidikan ?? 'S1',
            'prodi' => $request->prodi,
            'alamat' => $request->alamat,
            'nama_ayah' => $request->nama_ayah,
            'nama_ibu' => $request->nama_ibu,
            'ipk' => $request->ipk,
            'judul' => $request->judul,
            'tahun_lulus' => $request->tahun_lulus,
            'wisudawan_terbaik' => false,
            'berprestasi' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data peserta wisuda berhasil ditambahkan.',
            'data' => $peserta,
        ], 201);
    }

    /**
     * Menampilkan detail satu peserta wisuda.
     */
    public function show($npm)
    {
        // Menggunakan Route Model Binding, data sudah otomatis diambil.
        // Kita tambahkan relasi acara untuk ditampilkan di detail.
        $peserta_wisuda = PesertaWisuda::with('acara')->where('npm', $npm)->first();

        if(!$peserta_wisuda) {
            return ApiResponse::error('Peserta wisuda tidak ditemukan.', 404);
        }

        $peserta_wisuda->photo_url = $this->getPhotoUrl($peserta_wisuda->npm);

        return ApiResponse::success($peserta_wisuda, 'Detail peserta wisuda berhasil diambil.');
    }

    /**
     * Memperbarui data peserta wisuda di database.
     */
    public function update(Request $request, $id)
    {
        $peserta = PesertaWisuda::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Data peserta tidak ditemukan.',
            ], 404);
        }

        // Jika request hanya berupa pembaruan foto saja
        if ($request->hasFile('photo')) {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Maks 5MB sebelum dikompresi
            ]);

            // Hapus foto lama jika ada dan bukan gambar default
            if ($peserta->photo && !str_contains($peserta->photo, 'default.png')) {
                $oldPath = str_replace('/storage/', '', parse_url($peserta->photo, PHP_URL_PATH));
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Simpan foto baru ke folder storage/app/public/peserta-photos
            $path = $request->file('photo')->store('peserta-photos', 'public');
            $peserta->photo = Storage::url($path);
            $peserta->save();

            return response()->json([
                'success' => true,
                'message' => 'Foto peserta berhasil diperbarui.',
                'data' => $peserta,
            ], 200);
        }

        // Jika request memperbarui data fields lain
        $peserta->update($request->except(['photo']));

        return response()->json([
            'success' => true,
            'message' => 'Data peserta berhasil diperbarui.',
            'data' => $peserta,
        ], 200);
    }

    /**
     * Menghapus peserta wisuda dari database.
     */
    public function destroy(PesertaWisuda $peserta_wisuda)
    {
        try {
            $npm = $peserta_wisuda->npm;
            $peserta_wisuda->delete();

            $directoryPath = 'photo_peserta/' . $npm;
            if (Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->deleteDirectory($directoryPath);
            }
            return ApiResponse::success(null, 'Peserta wisuda berhasil dihapus.');
        } catch (Exception $e) {
            return ApiResponse::error('Gagal menghapus peserta: ' . $e->getMessage(), 500);
        }
    }

    public function uploadBulk(Request $request)
    {
        try {
            $uploadedData = [];

            foreach ($request->file('photos') as $file) {
                $npm = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                
                // [DIUBAH] Tentukan path tujuan langsung di dalam folder public
                $destinationPath = public_path('photo_peserta');
                $filename = $npm.'.jpg';

                // [DIUBAH] Pindahkan file secara manual menggunakan method move()
                // Method move() adalah wrapper Laravel untuk fungsi move_uploaded_file() PHP
                $file->move($destinationPath, $filename);

                // Buat path relatif untuk helper asset()
                $relativePath = 'photo_peserta/' . $filename;

                $uploadedData[] = [
                    'npm' => $npm,
                    'url' => asset($relativePath)
                ];
            }

            return ApiResponse::success($uploadedData, 'Foto-foto berhasil diunggah secara massal.');

        } catch (Exception $e) {
            return ApiResponse::error('Gagal mengunggah foto: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper function untuk mengambil semua URL foto dari folder NPM.
     */
    private function getPhotoUrl(?string $npm): ?string
    {
        // Jika NPM tidak ada, langsung kembalikan null
        if (!$npm) {
            return null;
        }
        
        // Buat path relatif yang akan digunakan untuk URL publik
        $relativePath = "photo_peserta/{$npm}.jpg";
        
        // Buat path absolut di server untuk memeriksa keberadaan file
        $fullPath = public_path($relativePath);

        // Jika file ada, kembalikan URL publiknya. Jika tidak, kembalikan null.
        if (File::exists($fullPath)) {
            return asset($relativePath);
        }

        return null;
    }

    public function importExcel(Request $request, $acaraId)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls|max:5120', // Maksimal 5MB
        ]);

        try {
            Excel::import(new PesertaWisudaImport($acaraId), $request->file('file_excel'));

            return response()->json([
                'success' => true,
                'message' => 'Data peserta wisuda berhasil diimpor.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getByIdAcara(Acara $acara)
    {
        try {
            $pesertaCollection = PesertaWisuda::where('acara_id', $acara->id)->get();

            $pesertaCollection->each(function ($peserta) {
                // Jika peserta memiliki foto yang diunggah manual, gunakan foto tersebut.
                // Jika tidak ada, gunakan helper URL foto berdasarkan NPM.
                if (!empty($peserta->photo)) {
                    // Pastikan URL memiliki domain lengkap jika merupakan path relatif
                    $peserta->photo = filter_var($peserta->photo, FILTER_VALIDATE_URL)
                        ? $peserta->photo
                        : asset($peserta->photo);
                } else {
                    $peserta->photo = $this->getPhotoUrl($peserta->npm);
                }
            });

            return ApiResponse::success($pesertaCollection, 'Data peserta wisuda berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    public function toggleTerbaik(PesertaWisuda $peserta_wisuda)
    {
        try {
            if($peserta_wisuda->wisudawan_terbaik == 1) {
                $peserta_wisuda->update(['wisudawan_terbaik' => 0]);
                return ApiResponse::success($peserta_wisuda, 'Data peserta wisuda berhasil diperbarui.');
            }
            $peserta_wisuda->update(['wisudawan_terbaik' => 1]);
            return ApiResponse::success($peserta_wisuda, 'Data peserta wisuda berhasil diperbarui.');
        } catch (Exception $e) {
            return ApiResponse::error('Gagal memperbarui peserta: ' . $e->getMessage(), 500);
        }
    }

    public function toggleBerprestasi(PesertaWisuda $peserta_wisuda)
    {
        try {
            if($peserta_wisuda->berprestasi == 1) {
                $peserta_wisuda->update(['berprestasi' => 0]);
                return ApiResponse::success($peserta_wisuda, 'Data peserta wisuda berhasil diperbarui.');
            }
            $peserta_wisuda->update(['berprestasi' => 1]);
            return ApiResponse::success($peserta_wisuda, 'Data peserta wisuda berhasil diperbarui.');
        } catch (Exception $e) {
            return ApiResponse::error('Gagal memperbarui peserta: ' . $e->getMessage(), 500);
        }
    }

    public function toggleInspiratif($id)
    {
        $peserta = PesertaWisuda::findOrFail($id);

        // Set eksplisit '1' jika sebelumnya '0'/false/null, atau '0' jika sebelumnya '1'/true
        $peserta->inspiratif = in_array($peserta->inspiratif, ['1', 1, true], true) ? '0' : '1';
        $peserta->save();

        return response()->json([
            'success' => true,
            'data' => $peserta
        ]);
    }


    public function terbaik($prodi)
    {
        try {
            $pesertaCollection = PesertaWisuda::where('prodi', $prodi)->with('acara')->where('wisudawan_terbaik', 1)->get();
            $pesertaCollection->each(function ($peserta) {
                $peserta->photo = $this->getPhotoUrl($peserta->npm);
            });
            return ApiResponse::success($pesertaCollection, 'Data peserta wisuda berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    public function berprestasi()
    {
        try {
            $pesertaCollection = PesertaWisuda::with('acara')->where('berprestasi', 1)->get();
            $pesertaCollection->each(function ($peserta) {
                $peserta->photo = $this->getPhotoUrl($peserta->npm);
            });
            return ApiResponse::success($pesertaCollection, 'Data peserta wisuda berhasil diambil.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

}
