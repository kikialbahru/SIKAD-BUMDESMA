<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class PurgeDemoEmployees extends Command
{
    protected $signature = 'demo:purge
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Nonaktifkan + hapus karyawan demo KRY-001 s/d KRY-006 (riwayat tetap tersimpan, akun login ikut terkunci).';

    public function handle(): int
    {
        $codes = ['KRY-001', 'KRY-002', 'KRY-003', 'KRY-004', 'KRY-005', 'KRY-006'];
        $targets = Employee::whereIn('kode_karyawan', $codes)->orderBy('kode_karyawan')->get();

        if ($targets->isEmpty()) {
            $this->info('Tidak ada karyawan demo (KRY-001 s/d KRY-006). Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        foreach ($targets as $employee) {
            $this->line("- {$employee->kode_karyawan} ({$employee->display_name})");
        }

        if (! $this->option('force') && ! $this->confirm("Nonaktifkan + hapus {$targets->count()} karyawan demo di atas?", false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        foreach ($targets as $employee) {
            // Sama seperti EmployeeController@destroy: riwayat (absensi/gaji/dll) tetap
            // tersimpan, dan login otomatis terkunci karena akun sudah tidak aktif.
            $employee->update(['aktif' => false]);
            $employee->delete();
        }

        $this->info("Berhasil menonaktifkan + menghapus {$targets->count()} karyawan demo.");

        return self::SUCCESS;
    }
}
