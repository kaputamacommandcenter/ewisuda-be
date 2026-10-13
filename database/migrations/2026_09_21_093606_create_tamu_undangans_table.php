<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tamu_undangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acara_id')->constrained('acaras')->onDelete('cascade');
            $table->string('nama');
            $table->string('instansi_jabatan')->nullable(); // misal: Rektor / Kepala Dinas
            $table->string('kategori')->default('VVIP'); // VVIP, VIP, Reguler
            $table->string('status_kehadiran')->default('Belum Konfirmasi'); // Hadir, Tidak Hadir, Belum Konfirmasi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tamu_undangans');
    }
};
