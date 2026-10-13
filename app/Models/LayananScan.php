<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayananScan extends Model
{
    use HasFactory;

    protected $table = 'layanan_scans';

    protected $fillable = [
        'nama_layanan',
        'kode_layanan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function riwayatScan()
    {
        return $this->hasMany(RiwayatScan::class, 'layanan_scan_id');
    }
}
