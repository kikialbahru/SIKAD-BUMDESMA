<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KaryawanBaruSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::updateOrCreate(['nama' => 'Staff'], ['deskripsi' => 'Staff BUMDESMA LKD TARUB']);
        $schedule = WorkSchedule::firstOrCreate(['nama' => 'Jam Kerja Reguler'], [
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 10,
            'hari_kerja' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);

        // Karyawan tetap (password awal "12345678", wajib diganti setelah login pertama).
        // Dijalankan juga di production — gunakan updateOrCreate agar aman dijalankan ulang.
        $karyawan = [
            ['code' => 'KRY-007', 'name' => 'Zila', 'email' => 'zila@bumdesma.test'],
            ['code' => 'KRY-008', 'name' => 'Sus', 'email' => 'sus@bumdesma.test'],
            ['code' => 'KRY-009', 'name' => 'Andry', 'email' => 'andry@bumdesma.test'],
            ['code' => 'KRY-010', 'name' => 'Oka', 'email' => 'oka@bumdesma.test'],
            ['code' => 'KRY-011', 'name' => 'Ludi', 'email' => 'ludi@bumdesma.test'],
            ['code' => 'KRY-012', 'name' => 'Zaeni', 'email' => 'zaeni@bumdesma.test'],
            ['code' => 'KRY-013', 'name' => 'Tamami', 'email' => 'tamami@bumdesma.test'],
        ];

        foreach ($karyawan as $item) {
            $user = User::updateOrCreate(['email' => $item['email']], [
                'name' => $item['name'],
                'role' => 'employee',
                'password' => Hash::make('12345678'),
            ]);

            Employee::updateOrCreate(['kode_karyawan' => $item['code']], [
                'pengguna_id' => $user->id,
                'nama' => $item['name'],
                'jabatan_id' => $position->id,
                'jadwal_kerja_id' => $schedule->id,
                'tanggal_masuk' => now()->toDateString(),
                'aktif' => true,
            ]);
        }
    }
}
