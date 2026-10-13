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
            // Menambahkan kolom background_image (nullable/opsional) setelah kolom link_google_map
            $table->string('background_image')->nullable()->after('link_google_map');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            // Menghapus kolom background_image jika migration di-rollback
            $table->dropColumn('background_image');
        });
    }
};
