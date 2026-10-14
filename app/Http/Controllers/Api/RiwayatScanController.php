<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiwayatScan;
use App\Models\Acara;
use Illuminate\Http\JsonResponse;

class RiwayatScanController extends Controller
{
    /**
     * Mengambil daftar riwayat pemindaian QR berdasarkan acara.
     *
     * @param int|string $eventId
     * @return JsonResponse
     */
    public function index($eventId): JsonResponse
    {
        // Validasi keberadaan acara
        $acara = Acara::find($eventId);
        if (!$acara) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Acara tidak ditemukan.',
            ], 404);
        }

        // Fetch riwayat scan menggunakan model RiwayatScan & relasinya (tamu & layanan)
        $riwayat = RiwayatScan::with(['tamu', 'layanan'])
            ->whereHas('tamu', function ($query) use ($eventId) {
                $query->where('acara_id', $eventId);
            })
            ->latest('waktu_scan') // Mengurutkan berdasarkan waktu pemindaian terbaru
            ->get()
            ->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'unique_code'     => $item->tamu->unique_code ?? '-',
                    'nama_tamu'       => $item->tamu->nama ?? '-',
                'kategori'        => $item->tamu->kategori ?? '-',
                    'instansi'        => $item->tamu->instansi ?? $item->tamu->prodi ?? '-',
                    'layanan_scan_id' => $item->layanan_scan_id,
                'waktu_scan'      => $item->waktu_scan ? $item->waktu_scan->format('Y-m-d H:i:s') : '-',
                'keterangan'      => $item->keterangan ?? '-',
                    'nama_layanan'    => $item->layanan->nama_layanan ?? '-',
                'status'          => 'success',
                    'created_at'      => $item->waktu_scan ? $item->waktu_scan->format('Y-m-d H:i:s') : '-',
                ];
            });

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil mengambil riwayat scan.',
            'data'    => $riwayat,
        ], 200);
    }

    public function getByAcara($acaraId = null)
    {
        try {
            $query = RiwayatScan::with(['tamu', 'layanan']);

            // Jika $acaraId adalah 'active' atau null, cari acara yang sedang aktif (is_active = 1)
            if (!$acaraId || $acaraId === 'active') {
                $acaraAktif = Acara::where('is_active', true)->first();

                if (!$acaraAktif) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Tidak ada acara yang sedang aktif saat ini.',
                        'data'    => [],
                    ], 200);
                }

                $query->whereHas('tamu', function ($q) use ($acaraAktif) {
                    $q->where('acara_id', $acaraAktif->id);
                });
            } else {
                // Jika ID acara tertentu ditentukan
                $query->whereHas('tamu', function ($q) use ($acaraId) {
                    $q->where('acara_id', $acaraId);
                });
            }

            $riwayat = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'              => $item->id,
                        'nama_tamu'       => $item->tamu?->nama ?? '-',
                        'kategori'        => $item->tamu?->kategori ?? 'Reguler',
                        'instansi'        => $item->tamu?->instansi ?? $item->tamu?->instansi_jabatan ?? '-',
                        'unique_code'     => $item->tamu?->unique_code ?? '-',
                        'layanan_scan_id' => $item->layanan_scan_id,
                        'nama_layanan'    => $item->layanan?->nama_layanan ?? '-',
                        'keterangan'      => $item->keterangan ?? '-',
                        'waktu_scan'      => $item->waktu_scan ? $item->waktu_scan->format('d/m/Y H:i:s') : $item->created_at->format('d/m/Y H:i:s'),
                        'status'          => 'success',
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Riwayat scan berhasil diambil.',
                'data'    => $riwayat,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data riwayat scan: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $riwayat = RiwayatScan::findOrFail($id);
            $riwayat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Log riwayat scan berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus riwayat scan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
