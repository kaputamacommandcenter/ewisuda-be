<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acaras', function (Blueprint $table) {
            $table->id();
            $table->string('kampus', 200);
            $table->enum('kegiatan', ['WISUDA'])->default('WISUDA');
            $table->string('keterangan', 200)->nullable();
            $table->string('tahun_akademik', 9);
            $table->string('lokasi', 100);
            $table->string('tanggal', 50);
            $table->char('is_active', 1)->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acaras');
    }
};
