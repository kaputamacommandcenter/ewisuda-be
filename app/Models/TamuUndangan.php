<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TamuUndangan extends Model
{
    protected $fillable = [
        'acara_id',
        'nama',
        'npm',
        'instansi',
        'kategori',
        'status_kehadiran',
        'unique_code',
        'is_hadir',
        'waktu_hadir',
    ];

    protected $casts = [
        'is_hadir' => 'boolean',
        'waktu_hadir' => 'datetime',
    ];

    public function acara()
    {
        return $this->belongsTo(Acara::class, 'acara_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tamu) {
            if (empty($tamu->unique_code)) {
                // Generates kode unik acak sepanjang 8 karakter uppercase
                $tamu->unique_code = strtoupper(Str::random(8));
            }
        });
    }
}
