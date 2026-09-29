<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur Konten Web dihapus: halaman publik memakai konten bawaan
     * di HomeController. Hapus tabelnya (data tidak dipulihkan).
     */
    public function up(): void
    {
        Schema::dropIfExists('konten');
        Schema::dropIfExists('contents');
    }

    public function down(): void
    {
        // Data konten tidak dapat dipulihkan — rollback hanya menahan rantai migrasi.
    }
};
