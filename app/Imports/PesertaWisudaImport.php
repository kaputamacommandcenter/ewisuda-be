<?php

namespace App\Imports;

use App\Models\PesertaWisuda;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class PesertaWisudaImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $acaraId;

    public function __construct($acaraId)
    {
        $this->acaraId = $acaraId;
    }

    /**
     * Memetakan baris dari file Excel ke Model PesertaWisuda
     */
    public function model(array $row)
    {
        // Konversi format tanggal lahir jika ada
        $tanggalLahir = null;
        if (!empty($row['tanggal_lahir'])) {
            try {
                if (is_numeric($row['tanggal_lahir'])) {
                    $tanggalLahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_lahir'])->format('Y-m-d');
                } else {
                    $tanggalLahir = Carbon::parse($row['tanggal_lahir'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $tanggalLahir = null;
            }
        }

        return new PesertaWisuda([
            'acara_id'           => $this->acaraId,
            'npm'                => trim($row['npm'] ?? $row['nim'] ?? ''),
            'nama'               => trim($row['nama'] ?? $row['nama_lengkap'] ?? ''),
            'tempat_lahir'       => $row['tempat_lahir'] ?? null,
            'tanggal_lahir'      => $tanggalLahir,
            'alamat'             => $row['alamat'] ?? null,
            'prodi'              => trim($row['prodi'] ?? $row['program_studi'] ?? ''),
            'jenjang_pendidikan' => trim($row['jenjang_pendidikan'] ?? $row['jenjang'] ?? 'Strata 1 (Sarjana)'),
            'judul'              => $row['judul'] ?? $row['judul_skripsi'] ?? $row['judul_ta'] ?? null,
            'ipk'                => isset($row['ipk']) ? (float)$row['ipk'] : null,
            'tahun_lulus'        => $row['tahun_lulus'] ?? null,
            'telp'               => $row['telp'] ?? $row['no_hp'] ?? $row['telepon'] ?? null,
            'nama_ayah'          => $row['nama_ayah'] ?? null,
            'nama_ibu'           => $row['nama_ibu'] ?? null,
            'wisudawan_terbaik'  => false,
            'berprestasi'      => false,
        ]);
    }

    /**
     * Rules Validasi Baris Excel
     */
    public function rules(): array
    {
        return [
            '*.npm'  => 'required',
            '*.nama' => 'required',
        ];
    }
}
