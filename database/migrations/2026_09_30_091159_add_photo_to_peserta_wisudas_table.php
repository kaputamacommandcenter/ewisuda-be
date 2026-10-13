<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta_wisudas', function (Blueprint $table) {
            // Menambahkan kolom photo setelah kolom telp (nullable/boleh kosong)
            $table->string('photo')->nullable()->after('telp');
        });
    }

    public function down(): void
    {
        Schema::table('peserta_wisudas', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
