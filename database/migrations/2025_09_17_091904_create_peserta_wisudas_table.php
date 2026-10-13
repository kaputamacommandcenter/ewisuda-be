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
        Schema::create('peserta_wisudas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acara_id')->nullable()->constrained('acaras')->onDelete('set null');
            $table->string('npm', 10)->nullable();
            $table->string('nama', 200)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 50)->nullable();
            $table->string('telp', 20)->nullable();
            $table->enum('jenjang_pendidikan', ['Strata 1 (Sarjana)', 'Diploma 3 (D3)', 'Strata 2 (Magister)', 'Diploma 4 (D4)', 'Strata 3 (Doktor)'])->default('Strata 1 (Sarjana)')->nullable();
            $table->string('prodi', 100)->nullable();
            $table->string('alamat')->nullable();
            $table->string('nama_ayah', 150)->nullable();
            $table->string('nama_ibu', 150)->nullable();
            $table->string('ipk', 5)->nullable();
            $table->text('judul')->nullable();
            $table->char('wisudawan_terbaik', 1)->default(0);
            $table->char('berprestasi', 1)->default(0);
            $table->string('keterangan_prestasi')->nullable();
            $table->string('tahun_lulus', 9)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_wisudas');
    }
};
