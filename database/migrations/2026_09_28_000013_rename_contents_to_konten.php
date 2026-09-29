<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 14 Indonesianisasi DB: contents -> konten.
     * Data dipertahankan (rename, bukan fresh).
     * Pakai statement mentah agar tidak butuh doctrine/dbal.
     * Kolom `slug`/`label` dipertahankan (slug URL & label tampil, netral).
     * Nilai `jenis` (page/slide/...) TIDAK diubah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('contents')) {
            return;
        }

        Schema::rename('contents', 'konten');

        DB::statement('ALTER TABLE `konten` CHANGE `type` `jenis` VARCHAR(30) NOT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `title` `judul` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `excerpt` `ringkasan` TEXT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `body` `isi` LONGTEXT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `image_url` `gambar_url` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `published_at` `diterbitkan_pada` TIMESTAMP NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `is_published` `terbit` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('konten')) {
            return;
        }

        DB::statement('ALTER TABLE `konten` CHANGE `jenis` `type` VARCHAR(30) NOT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `judul` `title` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `ringkasan` `excerpt` TEXT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `isi` `body` LONGTEXT NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `gambar_url` `image_url` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `diterbitkan_pada` `published_at` TIMESTAMP NULL');
        DB::statement('ALTER TABLE `konten` CHANGE `terbit` `is_published` TINYINT(1) NOT NULL DEFAULT 1');

        Schema::rename('konten', 'contents');
    }
};
