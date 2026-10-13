<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tamu_undangans', function (Blueprint $table) {
            $table->boolean('is_hadir')->default(false)->after('unique_code');
            $table->timestamp('waktu_hadir')->nullable()->after('is_hadir');
        });
    }

    public function down(): void
    {
        Schema::table('tamu_undangans', function (Blueprint $table) {
            $table->dropColumn(['is_hadir', 'waktu_hadir']);
        });
    }
};
