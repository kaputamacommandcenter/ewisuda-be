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
            // Menambahkan kolom music_link (opsional / nullable) setelah kolom background_image
            $table->string('music_link')->nullable()->after('background_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            // Menghapus kolom music_link jika di-rollback
            $table->dropColumn('music_link');
        });
    }
};
