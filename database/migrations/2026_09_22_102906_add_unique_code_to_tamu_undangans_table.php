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
            // Menambahkan kolom unique_code yang bersifat unik dan opsional (nullable)
            $table->string('unique_code')->unique()->nullable()->after('status_kehadiran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tamu_undangans', function (Blueprint $table) {
            $table->dropColumn('unique_code');
        });
    }
};
