<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 13 Indonesianisasi DB: visit_attendances -> kunjungan.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('visit_attendances')) {
            return;
        }

        Schema::table('visit_attendances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::rename('visit_attendances', 'kunjungan');

        DB::statement('ALTER TABLE `kunjungan` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `title` `judul` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `description` `deskripsi` TEXT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `photo` `foto` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `latitude` `lintang` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `longitude` `bujur` DECIMAL(11, 7) NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `accuracy` `akurasi` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `visit_date` `tanggal` DATE NOT NULL');

        Schema::table('kunjungan', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kunjungan')) {
            return;
        }

        Schema::table('kunjungan', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
        });

        DB::statement('ALTER TABLE `kunjungan` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `judul` `title` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `deskripsi` `description` TEXT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `foto` `photo` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `lintang` `latitude` DECIMAL(10, 7) NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `bujur` `longitude` DECIMAL(11, 7) NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `akurasi` `accuracy` INT UNSIGNED NULL');
        DB::statement('ALTER TABLE `kunjungan` CHANGE `tanggal` `visit_date` DATE NOT NULL');

        Schema::rename('kunjungan', 'visit_attendances');

        Schema::table('visit_attendances', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }
};
