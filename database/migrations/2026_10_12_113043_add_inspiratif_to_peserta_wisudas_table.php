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
        Schema::table('peserta_wisudas', function (Blueprint $table) {
            // Menambahkan kolom 'inspiratif' bertipe enum/boolean dengan default '0' setelah kolom 'berprestasi'
            $table->enum('inspiratif', ['0', '1'])->default('0')->after('berprestasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_wisudas', function (Blueprint $table) {
            $table->dropColumn('inspiratif');
        });
    }
};
