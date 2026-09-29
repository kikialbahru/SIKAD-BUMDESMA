<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2 Indonesianisasi DB: holidays -> hari_libur.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('holidays')) {
            return;
        }

        Schema::rename('holidays', 'hari_libur');

        DB::statement('ALTER TABLE `hari_libur` CHANGE `holiday_date` `tanggal` DATE NOT NULL');
        DB::statement('ALTER TABLE `hari_libur` CHANGE `name` `nama` VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('hari_libur')) {
            return;
        }

        DB::statement('ALTER TABLE `hari_libur` CHANGE `tanggal` `holiday_date` DATE NOT NULL');
        DB::statement('ALTER TABLE `hari_libur` CHANGE `nama` `name` VARCHAR(255) NOT NULL');

        Schema::rename('hari_libur', 'holidays');
    }
};
