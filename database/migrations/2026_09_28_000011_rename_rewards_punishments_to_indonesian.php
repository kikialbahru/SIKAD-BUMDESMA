<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 12 Indonesianisasi DB: rewards -> penghargaan, punishments -> hukuman.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Kolom `reward_rule_id`/`punishment_rule_id` TIDAK diubah (tabel aturan mati,
     * tidak dipakai kode — lihat AGENTS.md).
     * Nilai `jenis` TIDAK diubah.
     */
    public function up(): void
    {
        if (Schema::hasTable('rewards')) {
            Schema::table('rewards', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
            });

            Schema::rename('rewards', 'penghargaan');

            DB::statement('ALTER TABLE `penghargaan` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `type` `jenis` VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `amount` `nominal` DECIMAL(14, 2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `points` `poin` INT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `title` `judul` VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `description` `deskripsi` TEXT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `awarded_at` `diberikan_pada` DATE NOT NULL');

            Schema::table('penghargaan', function (Blueprint $table) {
                $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('punishments')) {
            Schema::table('punishments', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
            });

            Schema::rename('punishments', 'hukuman');

            DB::statement('ALTER TABLE `hukuman` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `type` `jenis` VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `amount` `nominal` DECIMAL(14, 2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `points` `poin` INT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `is_auto` `otomatis` TINYINT(1) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `title` `judul` VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `description` `deskripsi` TEXT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `issued_at` `dijatuhkan_pada` DATE NOT NULL');

            Schema::table('hukuman', function (Blueprint $table) {
                $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hukuman')) {
            Schema::table('hukuman', function (Blueprint $table) {
                $table->dropForeign(['karyawan_id']);
            });

            DB::statement('ALTER TABLE `hukuman` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `jenis` `type` VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `nominal` `amount` DECIMAL(14, 2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `poin` `points` INT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `otomatis` `is_auto` TINYINT(1) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `hukuman` CHANGE `judul` `title` VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `deskripsi` `description` TEXT NULL');
            DB::statement('ALTER TABLE `hukuman` CHANGE `dijatuhkan_pada` `issued_at` DATE NOT NULL');

            Schema::rename('hukuman', 'punishments');

            Schema::table('punishments', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('penghargaan')) {
            Schema::table('penghargaan', function (Blueprint $table) {
                $table->dropForeign(['karyawan_id']);
            });

            DB::statement('ALTER TABLE `penghargaan` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `jenis` `type` VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `nominal` `amount` DECIMAL(14, 2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `poin` `points` INT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `judul` `title` VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `deskripsi` `description` TEXT NULL');
            DB::statement('ALTER TABLE `penghargaan` CHANGE `diberikan_pada` `awarded_at` DATE NOT NULL');

            Schema::rename('penghargaan', 'rewards');

            Schema::table('rewards', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
            });
        }
    }
};
