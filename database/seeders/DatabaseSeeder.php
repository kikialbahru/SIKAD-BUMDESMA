<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AttendanceSeeder::class);
        $this->call(AttendanceLocationSeeder::class);
        // Karyawan tetap (KRY-007 dst.) dibuat di semua environment termasuk production.
        $this->call(KaryawanBaruSeeder::class);
    }
}
