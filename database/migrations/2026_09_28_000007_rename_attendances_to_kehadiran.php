<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 8 Indonesianisasi DB: attendances -> kehadiran.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Nilai enum `status`/`status_pulang` TIDAK diubah (fase terpisah bila diminta).
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['checkout_approved_by']);
        });

        Schema::rename('attendances', 'kehadiran');

        DB::statement('ALTER TABLE `kehadiran` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `attendance_date` `tanggal` DATE NOT NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_at` `waktu_masuk` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_at` `waktu_pulang` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_photo` `foto_masuk` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_latitude` `lintang_masuk` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_longitude` `bujur_masuk` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_accuracy` `akurasi_masuk` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_distance_meters` `jarak_masuk_meter` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_in_location_valid` `lokasi_masuk_valid` TINYINT(1) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_photo` `foto_pulang` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_latitude` `lintang_pulang` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_longitude` `bujur_pulang` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_accuracy` `akurasi_pulang` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_distance_meters` `jarak_pulang_meter` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `check_out_location_valid` `lokasi_pulang_valid` TINYINT(1) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `late_minutes` `menit_telat` INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `work_minutes` `menit_kerja` INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `checkout_status` `status_pulang` VARCHAR(20) NOT NULL DEFAULT "waiting"');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `checkout_approval_type` `tipe_persetujuan_pulang` VARCHAR(20) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `checkout_approved_at` `disetujui_pada` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `checkout_approved_by` `disetujui_oleh` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `note` `catatan` TEXT NULL');

        Schema::table('kehadiran', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
            $table->foreign('disetujui_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kehadiran')) {
            return;
        }

        Schema::table('kehadiran', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropForeign(['disetujui_oleh']);
        });

        DB::statement('ALTER TABLE `kehadiran` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `tanggal` `attendance_date` DATE NOT NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `waktu_masuk` `check_in_at` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `waktu_pulang` `check_out_at` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `foto_masuk` `check_in_photo` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `lintang_masuk` `check_in_latitude` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `bujur_masuk` `check_in_longitude` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `akurasi_masuk` `check_in_accuracy` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `jarak_masuk_meter` `check_in_distance_meters` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `lokasi_masuk_valid` `check_in_location_valid` TINYINT(1) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `foto_pulang` `check_out_photo` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `lintang_pulang` `check_out_latitude` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `bujur_pulang` `check_out_longitude` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `akurasi_pulang` `check_out_accuracy` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `jarak_pulang_meter` `check_out_distance_meters` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `lokasi_pulang_valid` `check_out_location_valid` TINYINT(1) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `menit_telat` `late_minutes` INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `menit_kerja` `work_minutes` INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `status_pulang` `checkout_status` VARCHAR(20) NOT NULL DEFAULT "waiting"');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `tipe_persetujuan_pulang` `checkout_approval_type` VARCHAR(20) NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `disetujui_pada` `checkout_approved_at` DATETIME NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `disetujui_oleh` `checkout_approved_by` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kehadiran` CHANGE `catatan` `note` TEXT NULL');

        Schema::rename('kehadiran', 'attendances');

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
            $table->foreign('checkout_approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
