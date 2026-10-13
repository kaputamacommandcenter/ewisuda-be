<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Master Layanan Scan (Independen/Global)
        Schema::create('layanan_scans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_layanan');
            $table->string('kode_layanan')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Riwayat Scan
        Schema::create('riwayat_scan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tamu_undangan_id')->constrained('tamu_undangans')->onDelete('cascade');
            $table->foreignId('layanan_scan_id')->constrained('layanan_scans')->onDelete('cascade');
            $table->timestamp('waktu_scan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_scans');
        Schema::dropIfExists('layanan_scans');
    }
};
