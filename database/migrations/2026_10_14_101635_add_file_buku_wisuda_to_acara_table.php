<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            $table->string('file_buku_wisuda')->nullable()->after('background_image');
        });
    }

    public function down(): void
    {
        Schema::table('acara', function (Blueprint $table) {
            $table->dropColumn('file_buku_wisuda');
        });
    }
};
