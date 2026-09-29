<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::updateOrCreate(['nama' => 'Staff'], ['deskripsi' => 'Staff BUMDESMA LKD TARUB']);
        $schedule = WorkSchedule::firstOrCreate(['nama' => 'Jam Kerja Reguler'], [
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 15,
            'hari_kerja' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);

        // Akun demo (password "password") hanya dibuat di luar production.
        // Di production, buat akun admin via `php artisan admin:create`.
        if (app()->isProduction()) {
            return;
        }

        $admin = User::updateOrCreate(['email' => 'admin@bumdesma.test'], [
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $admin->update(['role' => 'admin']);
    }
}
