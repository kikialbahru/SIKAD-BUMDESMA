<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_kehadiran', function (Blueprint $table) {
            $table->time('mulai_absen_masuk')->default('07:00:00')->after('jam_pulang');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_kehadiran', function (Blueprint $table) {
            $table->dropColumn('mulai_absen_masuk');
        });
    }
};
