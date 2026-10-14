<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LayananScan;
use App\Models\RiwayatScan;
use App\Models\TamuUndangan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LayananScanController extends Controller
{
    /**
     * Ambil semua daftar layanan scan yang aktif
     */
    public function index()
    {
        $layanan = LayananScan::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar layanan scan berhasil diambil.',
            'data' => $layanan,
        ], 200);
    }

    /**
     * Tambah layanan scan baru oleh Admin
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'kode_layanan' => 'required|string|unique:layanan_scan,kode_layanan',
        ]);

        $layanan = LayananScan::create([
            'nama_layanan' => $request->nama_layanan,
            'kode_layanan' => strtoupper(trim($request->kode_layanan)),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Layanan scan berhasil ditambahkan.',
            'data' => $layanan,
        ], 201);
    }

    /**
     * Toggle status aktif / non-aktif layanan
     */
    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $layanan = LayananScan::findOrFail($id);
        $layanan->update([
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status layanan scan berhasil diperbarui.',
            'data' => $layanan,
        ], 200);
    }

    /**
     * Hapus layanan scan
     */
    public function destroy($id)
    {
        $layanan = LayananScan::findOrFail($id);
        $layanan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Layanan scan berhasil dihapus.',
        ], 200);
    }

    public function checkCode($code)
    {
        $code = trim($code);
        $tamu = TamuUndangan::where('unique_code', $code)->first();

        if (!$tamu) {
            return response()->json([
                'success' => false,
                'message' => 'Kode unik tidak valid atau tamu tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data tamu ditemukan.',
            'data'    => [
                'id'          => $tamu->id,
                'nama'        => $tamu->nama,
                'instansi'    => $tamu->instansi ?? $tamu->instansi_jabatan ?? '-',
                'unique_code' => $tamu->unique_code,
            ]
        ], 200);
    }

    public function processScan(Request $request)
    {
        $request->validate([
            'unique_code'     => 'required|string',
            'layanan_scan_id' => 'required|exists:layanan_scans,id',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $code = trim($request->unique_code);
        $layananId = $request->layanan_scan_id;
        $keterangan = $request->keterangan ? trim($request->keterangan) : null;

        // Cari tamu berdasarkan unique_code
        $tamu = TamuUndangan::where('unique_code', $code)->first();

        if (!$tamu) {
            return response()->json([
                'success' => false,
                'message' => 'Kode unik tidak valid atau tamu tidak ditemukan.',
            ], 404);
        }

        $layanan = LayananScan::findOrFail($layananId);

        // Cek apakah tamu sudah pernah scan untuk layanan ini
        $sudahScan = RiwayatScan::where('tamu_undangan_id', $tamu->id)
            ->where('layanan_scan_id', $layananId)
            ->first();

        if ($sudahScan) {
            $waktuExisting = $sudahScan->waktu_scan
                ? Carbon::parse($sudahScan->waktu_scan)->setTimezone('Asia/Jakarta')->format('H:i') . ' WIB'
                : Carbon::parse($sudahScan->created_at)->setTimezone('Asia/Jakarta')->format('H:i') . ' WIB';

            return response()->json([
                'success' => false,
                'message' => "Tamu ini sudah melakukan scan untuk layanan {$layanan->nama_layanan}.",
                'data' => [
                    'nama'         => $tamu->nama,
                    'instansi'     => $tamu->instansi ?? $tamu->instansi_jabatan ?? '-',
                    'unique_code'  => $tamu->unique_code,
                    'nama_layanan' => $layanan->nama_layanan,
                    'waktu_scan'   => $waktuExisting,
                    'keterangan'   => $sudahScan->keterangan ?? '-',
                ]
            ], 400);
        }

        // Catat Waktu Aktual
        $now = Carbon::now('Asia/Jakarta');

        // Simpan ke RiwayatScan dengan Keterangan
        $riwayat = RiwayatScan::create([
            'tamu_undangan_id' => $tamu->id,
            'layanan_scan_id'  => $layanan->id,
            'waktu_scan'       => $now->toDateTimeString(),
            'keterangan'       => $keterangan,
        ]);

        // Jika Layanan adalah Kehadiran Utama, update juga tabel tamu_undangan
        if (strtoupper($layanan->kode_layanan) === 'KEHADIRAN' || $layananId == 1) {
            $tamu->update([
                'is_hadir'         => true,
                'status_kehadiran' => 'Hadir',
                'waktu_hadir'      => $now->toDateTimeString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Scan {$layanan->nama_layanan} berhasil dicatat.",
            'data' => [
                'nama'         => $tamu->nama,
                'instansi'     => $tamu->instansi ?? $tamu->instansi_jabatan ?? '-',
                'unique_code'  => $tamu->unique_code,
                'nama_layanan' => $layanan->nama_layanan,
                'waktu_scan'   => $now->format('H:i') . ' WIB',
                'keterangan'   => $keterangan ?? '-',
            ]
        ], 200);
    }
}
