<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatScan extends Model
{
    protected $table = 'riwayat_scans';

    protected $fillable = [
        'tamu_undangan_id',
        'layanan_scan_id',
        'waktu_scan',
    ];

    protected $casts = [
        'waktu_scan' => 'datetime',
    ];

    public function tamu()
    {
        return $this->belongsTo(TamuUndangan::class, 'tamu_undangan_id');
    }

    public function layanan()
    {
        return $this->belongsTo(LayananScan::class, 'layanan_scan_id');
    }
}
