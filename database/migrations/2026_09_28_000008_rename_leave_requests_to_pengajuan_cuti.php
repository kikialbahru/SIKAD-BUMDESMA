<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 9 Indonesianisasi DB: leave_requests -> pengajuan_cuti.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Nilai `jenis` (leave/sick) dan `status` TIDAK diubah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['reviewed_by']);
        });

        Schema::rename('leave_requests', 'pengajuan_cuti');

        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `type` `jenis` VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `start_date` `tanggal_mulai` DATE NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `end_date` `tanggal_selesai` DATE NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `total_days` `jumlah_hari` SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `reason` `alasan` TEXT NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `attachment_path` `jalur_lampiran` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `reviewed_by` `diulas_oleh` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `reviewed_at` `diulas_pada` DATETIME NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `review_note` `catatan_ulasan` TEXT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `rejection_reason` `alasan_penolakan` TEXT NULL');

        Schema::table('pengajuan_cuti', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
            $table->foreign('diulas_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengajuan_cuti')) {
            return;
        }

        Schema::table('pengajuan_cuti', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropForeign(['diulas_oleh']);
        });

        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `jenis` `type` VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `tanggal_mulai` `start_date` DATE NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `tanggal_selesai` `end_date` DATE NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `jumlah_hari` `total_days` SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `alasan` `reason` TEXT NOT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `jalur_lampiran` `attachment_path` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `diulas_oleh` `reviewed_by` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `diulas_pada` `reviewed_at` DATETIME NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `catatan_ulasan` `review_note` TEXT NULL');
        DB::statement('ALTER TABLE `pengajuan_cuti` CHANGE `alasan_penolakan` `rejection_reason` TEXT NULL');

        Schema::rename('pengajuan_cuti', 'leave_requests');

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
