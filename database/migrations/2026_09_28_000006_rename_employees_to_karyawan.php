<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 7 Indonesianisasi DB: employees -> karyawan.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Kolom `email` dipertahankan (sama dalam bahasa Indonesia).
     * Kolom `employee_id` di tabel lain TIDAK diubah (fase masing-masing).
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['work_schedule_id']);
        });

        Schema::rename('employees', 'karyawan');

        DB::statement('ALTER TABLE `karyawan` CHANGE `user_id` `pengguna_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `position_id` `jabatan_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `work_schedule_id` `jadwal_kerja_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `employee_code` `kode_karyawan` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `name` `nama` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `phone` `telepon` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `photo` `foto` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `photo_updated_at` `foto_diubah_pada` DATETIME NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `joined_at` `tanggal_masuk` DATE NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `basic_salary` `gaji_pokok` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `karyawan` CHANGE `attendance_allowance` `tunjangan_kehadiran` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `karyawan` CHANGE `is_active` `aktif` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::table('karyawan', function (Blueprint $table) {
            $table->foreign('pengguna_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('jabatan_id')->references('id')->on('jabatan')->nullOnDelete();
            $table->foreign('jadwal_kerja_id')->references('id')->on('jadwal_kerja')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('karyawan')) {
            return;
        }

        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['pengguna_id']);
            $table->dropForeign(['jabatan_id']);
            $table->dropForeign(['jadwal_kerja_id']);
        });

        DB::statement('ALTER TABLE `karyawan` CHANGE `pengguna_id` `user_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `jabatan_id` `position_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `jadwal_kerja_id` `work_schedule_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `kode_karyawan` `employee_code` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `nama` `name` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `telepon` `phone` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `foto` `photo` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `foto_diubah_pada` `photo_updated_at` DATETIME NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `tanggal_masuk` `joined_at` DATE NULL');
        DB::statement('ALTER TABLE `karyawan` CHANGE `gaji_pokok` `basic_salary` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `karyawan` CHANGE `tunjangan_kehadiran` `attendance_allowance` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `karyawan` CHANGE `aktif` `is_active` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::rename('karyawan', 'employees');

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('jabatan')->nullOnDelete();
            $table->foreign('work_schedule_id')->references('id')->on('jadwal_kerja')->nullOnDelete();
        });
    }
};
