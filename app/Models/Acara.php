<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acara extends Model
{
    protected $guarded = [];

    /**
     * Casting atribut JSON menjadi array
     */
    protected $casts = [
        'pejabat_tandatangan' => 'array',
        'tata_tertib'         => 'array',
        'tanggal_wisuda'      => 'date',
    ];

    /**
     * Relasi ke PesertaWisuda
     */
    public function peserta()
    {
        return $this->hasMany(PesertaWisuda::class);
    }
}
