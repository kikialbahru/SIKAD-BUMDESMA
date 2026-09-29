<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 3 Indonesianisasi DB: work_schedules -> jadwal_kerja.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Kolom attendance_settings TIDAK disentuh (fase terpisah).
     */
    public function up(): void
    {
        if (! Schema::hasTable('work_schedules')) {
            return;
        }

        Schema::rename('work_schedules', 'jadwal_kerja');

        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `name` `nama` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `start_time` `jam_masuk` TIME NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `end_time` `jam_pulang` TIME NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `late_tolerance_minutes` `toleransi_telat_menit` SMALLINT UNSIGNED NOT NULL DEFAULT 15');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `working_days` `hari_kerja` JSON NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `is_active` `aktif` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('jadwal_kerja')) {
            return;
        }

        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `nama` `name` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `jam_masuk` `start_time` TIME NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `jam_pulang` `end_time` TIME NOT NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `toleransi_telat_menit` `late_tolerance_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 15');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `hari_kerja` `working_days` JSON NULL');
        DB::statement('ALTER TABLE `jadwal_kerja` CHANGE `aktif` `is_active` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::rename('jadwal_kerja', 'work_schedules');
    }
};
