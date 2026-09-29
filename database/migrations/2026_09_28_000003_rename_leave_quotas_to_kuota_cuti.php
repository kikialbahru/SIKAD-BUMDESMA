<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 4 Indonesianisasi DB: leave_quotas -> kuota_cuti.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('leave_quotas')) {
            return;
        }

        Schema::rename('leave_quotas', 'kuota_cuti');

        DB::statement('ALTER TABLE `kuota_cuti` CHANGE `year` `tahun` SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kuota_cuti` CHANGE `quota_days` `jatah_hari` SMALLINT UNSIGNED NOT NULL DEFAULT 12');
    }

    public function down(): void
    {
        if (! Schema::hasTable('kuota_cuti')) {
            return;
        }

        DB::statement('ALTER TABLE `kuota_cuti` CHANGE `tahun` `year` SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `kuota_cuti` CHANGE `jatah_hari` `quota_days` SMALLINT UNSIGNED NOT NULL DEFAULT 12');

        Schema::rename('kuota_cuti', 'leave_quotas');
    }
};
