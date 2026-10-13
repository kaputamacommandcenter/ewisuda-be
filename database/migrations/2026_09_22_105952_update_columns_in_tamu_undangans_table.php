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
        Schema::table('tamu_undangans', function (Blueprint $table) {
            // Ubah nama kolom instansi_jabatan menjadi instansi
            $table->renameColumn('instansi_jabatan', 'instansi');

            // Tambahkan kolom npm setelah kolom nama
            $table->string('npm')->nullable()->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tamu_undangans', function (Blueprint $table) {
            // Kembalikan nama kolom instansi menjadi instansi_jabatan
            $table->renameColumn('instansi', 'instansi_jabatan');

            // Hapus kolom npm
            $table->dropColumn('npm');
        });
    }
};
