<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaWisuda extends Model
{
    protected $guarded = [];

    public function acara()
    {
        return $this->belongsTo(Acara::class, 'acara_id');
    }
}
