<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 5 Indonesianisasi DB: attendance_settings -> pengaturan_kehadiran.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        Schema::rename('attendance_settings', 'pengaturan_kehadiran');

        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `work_start_time` `jam_masuk` TIME NOT NULL DEFAULT "08:00:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `work_end_time` `jam_pulang` TIME NOT NULL DEFAULT "15:00:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `late_tolerance_minutes` `toleransi_telat_menit` SMALLINT UNSIGNED NOT NULL DEFAULT 30');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `checkout_start_time` `mulai_absen_pulang` TIME NOT NULL DEFAULT "14:30:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `checkout_end_time` `akhir_absen_pulang` TIME NOT NULL DEFAULT "15:30:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `require_checkout_approval` `wajib_persetujuan_pulang` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `auto_late_points_enabled` `poin_otomatis_aktif` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `late_points_block_minutes` `blok_menit_poin` SMALLINT UNSIGNED NOT NULL DEFAULT 15');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `late_points_per_block` `poin_per_blok` SMALLINT UNSIGNED NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengaturan_kehadiran')) {
            return;
        }

        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `jam_masuk` `work_start_time` TIME NOT NULL DEFAULT "08:00:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `jam_pulang` `work_end_time` TIME NOT NULL DEFAULT "15:00:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `toleransi_telat_menit` `late_tolerance_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 30');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `mulai_absen_pulang` `checkout_start_time` TIME NOT NULL DEFAULT "14:30:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `akhir_absen_pulang` `checkout_end_time` TIME NOT NULL DEFAULT "15:30:00"');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `wajib_persetujuan_pulang` `require_checkout_approval` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `poin_otomatis_aktif` `auto_late_points_enabled` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `blok_menit_poin` `late_points_block_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 15');
        DB::statement('ALTER TABLE `pengaturan_kehadiran` CHANGE `poin_per_blok` `late_points_per_block` SMALLINT UNSIGNED NOT NULL DEFAULT 1');

        Schema::rename('pengaturan_kehadiran', 'attendance_settings');
    }
};
