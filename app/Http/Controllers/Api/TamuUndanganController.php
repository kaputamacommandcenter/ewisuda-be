<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TamuUndangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PesertaWisuda;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Exception;
use Carbon\Carbon;

class TamuUndanganController extends Controller
{
    public function syncWisudawan($eventId)
    {
        try {
            // 1. Ambil data wisudawan berdasarkan id acara
            $pesertas = PesertaWisuda::where('acara_id', $eventId)->get();

            if ($pesertas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data peserta wisuda yang ditemukan untuk acara ini.'
                ], 404); // Dikoreksi status code ke 404 Not Found
            }

            $insertedCount = 0;
            $updatedCount = 0;

            DB::beginTransaction();

            foreach ($pesertas as $peserta) {
                $npmPeserta = $peserta->npm ?? $peserta->nim;

                // 2. Cek apakah wisudawan sudah ada berdasarkan npm/nama dan acara_id
                $tamu = TamuUndangan::where('acara_id', $eventId)
                    ->where(function ($query) use ($peserta, $npmPeserta) {
                        $query->where('npm', $npmPeserta)
                            ->orWhere('nama', $peserta->nama);
                    })
                    ->first();

                if (!$tamu) {
                    // 3. Buat data tamu baru dengan struktur kolom 'npm' dan 'instansi'
                    TamuUndangan::create([
                        'acara_id'         => $eventId,
                        'nama'             => $peserta->nama,
                        'npm'              => $npmPeserta,
                        'instansi'         => $peserta->prodi ?? 'Wisudawan',
                        'kategori'         => 'Mahasiswa',
                        'status_kehadiran' => 'Belum Konfirmasi',
                        'unique_code'      => strtoupper(Str::random(8)), // Kode unik untuk QR Code
                    ]);
                    $insertedCount++;
                } else {
                    // 4. Perbarui data jika npm/unique_code belum terisi sebelumnya
                    $dataToUpdate = [];

                    if (empty($tamu->npm)) {
                        $dataToUpdate['npm'] = $npmPeserta;
                    }

                    if (empty($tamu->unique_code)) {
                        $dataToUpdate['unique_code'] = strtoupper(Str::random(8));
                    }

                    if (!empty($dataToUpdate)) {
                        $tamu->update($dataToUpdate);
                        $updatedCount++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menarik data wisudawan. ({$insertedCount} tamu baru ditambahkan, {$updatedCount} diperbarui)",
                'data'    => [
                    'inserted' => $insertedCount,
                    'updated'  => $updatedCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menarik data wisudawan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByAcara($acaraId)
    {
        $tamus = TamuUndangan::where('acara_id', $acaraId)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $tamus]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'acara_id' => 'required|exists:acaras,id',
            'nama' => 'required|string|max:255',
            'instansi_jabatan' => 'nullable|string|max:255',
            'kategori' => 'required|string',
        ]);

        $tamu = TamuUndangan::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tamu undangan berhasil ditambahkan.',
            'data' => $tamu
        ], 201);
    }

    public function update(Request $request, TamuUndangan $tamuUndangan)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'instansi_jabatan' => 'nullable|string|max:255',
            'kategori' => 'required|string',
            'status_kehadiran' => 'required|string',
        ]);

        // Gunakan operator panah (->), bukan (::)
        $tamuUndangan->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Data tamu berhasil diperbarui.'
        ]);
    }

    public function destroy(TamuUndangan $tamuUndangan)
    {
        $tamuUndangan->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Tamu undangan berhasil dihapus.'
        ]);
    }

    // TamuUndanganController.php
    public function showByUniqueCode($code)
    {
        try {
            $tamu = TamuUndangan::where('unique_code', $code)->first();

            if (!$tamu) {
                return ApiResponse::error('Data tamu tidak ditemukan.', 404);
            }

            return ApiResponse::success($tamu, 'Data tamu berhasil ditemukan.');
        } catch (Exception $e) {
            return ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
        }
    }

    public function presensi(Request $request)
    {
        $request->validate([
            'unique_code' => 'required|string',
        ]);

        $code = trim($request->unique_code);

        // Cari tamu berdasarkan unique_code
        $tamu = TamuUndangan::where('unique_code', $code)->first();

        // 1. Jika kode unik tidak ditemukan
        if (!$tamu) {
            return response()->json([
                'success' => false,
                'message' => 'Kode unik tidak valid atau tamu tidak ditemukan.',
            ], 404);
        }

        // 2. Cek jika tamu sudah dicatat hadir sebelumnya
        if ($tamu->is_hadir) {
            $waktuFormatted = $tamu->waktu_hadir
                ? Carbon::parse($tamu->waktu_hadir)->format('H:i') . ' WIB'
                : '-';

            return response()->json([
                'success' => false,
                'message' => 'Tamu undangan ini sudah hadir sebelumnya.',
                'data' => [
                    'nama' => $tamu->nama,
                    'instansi' => $tamu->instansi ?? '-',
                    'unique_code' => $tamu->unique_code,
                    'waktu_hadir' => $waktuFormatted,
                ]
            ], 400);
        }

        // 3. Simpan data presensi kehadiran
        $now = Carbon::now();
        $tamu->update([
            'is_hadir' => true,
            'status_kehadiran' => 'Hadir', // Mengisi status_kehadiran sesuai struktur model Anda
            'waktu_hadir' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Presensi kehadiran berhasil dicatat.',
            'data' => [
                'nama' => $tamu->nama,
                'instansi' => $tamu->instansi ?? '-',
                'unique_code' => $tamu->unique_code,
                'waktu_hadir' => $now->format('H:i') . ' WIB',
            ]
        ], 200);
    }
}
