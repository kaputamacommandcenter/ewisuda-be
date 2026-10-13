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
        Schema::table('acaras', function (Blueprint $table) {
            $table->string('nama_perguruan_tinggi')->nullable()->after('kegiatan');
            $table->text('deskripsi')->nullable()->after('nama_perguruan_tinggi');
            $table->string('gedung_wisuda')->nullable()->after('deskripsi');
            $table->text('alamat_gedung')->nullable()->after('gedung_wisuda');
            $table->date('tanggal_wisuda')->nullable()->after('alamat_gedung');
            $table->string('waktu_wisuda')->nullable()->after('tanggal_wisuda');
            $table->text('link_google_map')->nullable()->after('waktu_wisuda');
            $table->json('pejabat_tandatangan')->nullable()->after('link_google_map');
            $table->json('tata_tertib')->nullable()->after('pejabat_tandatangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            $table->dropColumn([
                'nama_perguruan_tinggi',
                'deskripsi',
                'gedung_wisuda',
                'alamat_gedung',
                'tanggal_wisuda',
                'waktu_wisuda',
                'link_google_map',
                'pejabat_tandatangan',
                'tata_tertib',
            ]);
        });
    }
};
