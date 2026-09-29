<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 1 Indonesianisasi DB: positions -> jabatan.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        Schema::rename('positions', 'jabatan');

        DB::statement('ALTER TABLE `jabatan` CHANGE `name` `nama` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `jabatan` CHANGE `description` `deskripsi` TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('jabatan')) {
            return;
        }

        DB::statement('ALTER TABLE `jabatan` CHANGE `nama` `name` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `jabatan` CHANGE `deskripsi` `description` TEXT NULL');

        Schema::rename('jabatan', 'positions');
    }
};
