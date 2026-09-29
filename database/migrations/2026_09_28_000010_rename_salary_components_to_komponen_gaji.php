<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 11 Indonesianisasi DB: salary_components -> komponen_gaji.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Nilai `jenis` (late_deduction/absence_deduction/...) TIDAK diubah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('salary_components')) {
            return;
        }

        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::rename('salary_components', 'komponen_gaji');

        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `name` `nama` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `type` `jenis` VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `amount` `nominal` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `is_active` `aktif` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::table('komponen_gaji', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('komponen_gaji')) {
            return;
        }

        Schema::table('komponen_gaji', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
        });

        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `nama` `name` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `jenis` `type` VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `nominal` `amount` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `komponen_gaji` CHANGE `aktif` `is_active` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::rename('komponen_gaji', 'salary_components');

        Schema::table('salary_components', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }
};
