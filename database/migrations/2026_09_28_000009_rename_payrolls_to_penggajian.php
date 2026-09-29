<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 10 Indonesianisasi DB: payrolls -> penggajian.
     * Data dipertahankan (rename, bukan fresh).
     * FK di-drop dulu agar CHANGE kolom tidak ditolak MySQL, lalu dibuat ulang.
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Nilai `status` (draft/verified/paid) TIDAK diubah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payrolls')) {
            return;
        }

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::rename('payrolls', 'penggajian');

        DB::statement('ALTER TABLE `penggajian` CHANGE `employee_id` `karyawan_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `period_start` `periode_mulai` DATE NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `period_end` `periode_selesai` DATE NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `basic_salary` `gaji_pokok` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `attendance_allowance` `tunjangan_kehadiran` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `reward_total` `total_reward` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `late_deduction` `potongan_telat` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `absence_deduction` `potongan_absen` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `punishment_total` `total_hukuman` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `net_salary` `gaji_bersih` DECIMAL(14, 2) NOT NULL DEFAULT 0');

        Schema::table('penggajian', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('penggajian')) {
            return;
        }

        Schema::table('penggajian', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
        });

        DB::statement('ALTER TABLE `penggajian` CHANGE `karyawan_id` `employee_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `periode_mulai` `period_start` DATE NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `periode_selesai` `period_end` DATE NOT NULL');
        DB::statement('ALTER TABLE `penggajian` CHANGE `gaji_pokok` `basic_salary` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `tunjangan_kehadiran` `attendance_allowance` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `total_reward` `reward_total` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `potongan_telat` `late_deduction` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `potongan_absen` `absence_deduction` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `total_hukuman` `punishment_total` DECIMAL(14, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `penggajian` CHANGE `gaji_bersih` `net_salary` DECIMAL(14, 2) NOT NULL DEFAULT 0');

        Schema::rename('penggajian', 'payrolls');

        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('karyawan')->cascadeOnDelete();
        });
    }
};
