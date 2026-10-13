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
            // Menghapus kolom 'lokasi' dan 'tanggal' dari tabel acaras
            $table->dropColumn(['lokasi', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            // Mengembalikan kolom jika migration di-rollback
            $table->string('lokasi')->nullable();
            $table->string('tanggal')->nullable();
        });
    }
};
