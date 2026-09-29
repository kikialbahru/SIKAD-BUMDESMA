<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 6 Indonesianisasi DB: attendance_locations -> lokasi_kehadiran.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendance_locations')) {
            return;
        }

        Schema::rename('attendance_locations', 'lokasi_kehadiran');

        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `name` `nama` VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `address` `alamat` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `latitude` `lintang` DECIMAL(10, 7) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `longitude` `bujur` DECIMAL(10, 7) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `radius_meters` `radius_meter` INT UNSIGNED NOT NULL DEFAULT 100');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `max_accuracy_meters` `maks_akurasi_meter` INT UNSIGNED NOT NULL DEFAULT 100');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `enforce_radius` `wajib_radius` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `enforce_checkout_radius` `wajib_radius_pulang` TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `is_active` `aktif` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('lokasi_kehadiran')) {
            return;
        }

        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `nama` `name` VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `alamat` `address` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `lintang` `latitude` DECIMAL(10, 7) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `bujur` `longitude` DECIMAL(10, 7) NOT NULL');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `radius_meter` `radius_meters` INT UNSIGNED NOT NULL DEFAULT 100');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `maks_akurasi_meter` `max_accuracy_meters` INT UNSIGNED NOT NULL DEFAULT 100');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `wajib_radius` `enforce_radius` TINYINT(1) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `wajib_radius_pulang` `enforce_checkout_radius` TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `lokasi_kehadiran` CHANGE `aktif` `is_active` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::rename('lokasi_kehadiran', 'attendance_locations');
    }
};
