<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $location = AttendanceLocation::updateOrCreate(
                ['nama' => 'Kantor BUMDESMA TARUB LKD'],
                [
                    'alamat' => 'Kantor Bumdesma Tarub LKD, Tarub, Kabupaten Tegal 52184',
                    'lintang' => -6.926433,
                    'bujur' => 109.185844,
                    'radius_meter' => 100,
                    // Longgar agar GPS laptop (WiFi, akurasi ratusan meter) tetap bisa absen;
                    // admin bisa mengetatkan lagi via Kehadiran → Lokasi bila absen hanya via HP.
                    'maks_akurasi_meter' => 500,
                    'wajib_radius' => true,
                    'aktif' => true,
                ]
            );

            AttendanceLocation::whereKeyNot($location->id)->update(['aktif' => false]);
        });
    }
}
