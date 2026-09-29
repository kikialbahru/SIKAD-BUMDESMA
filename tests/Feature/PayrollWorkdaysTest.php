<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollWorkdaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_excluded_from_payroll_workdays(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $this->assertTrue(Carbon::parse('2026-09-10')->isWeekday());

        $admin = User::create([
            'name' => 'Admin Payroll',
            'email' => 'admin-payroll@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
        $user = User::create([
            'name' => 'Karyawan Payroll',
            'email' => 'kry-payroll@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        $employee = Employee::create([
            'pengguna_id' => $user->id,
            'kode_karyawan' => 'KRY-PAY',
            'aktif' => true,
        ]);
        SalaryComponent::create([
            'karyawan_id' => $employee->id,
            'jenis' => 'absence_deduction',
            'nama' => 'Tarif absen',
            'nominal' => 7000,
            'aktif' => true,
        ]);
        Holiday::create(['tanggal' => '2026-09-10', 'nama' => 'Libur uji']);

        // September 2026 = 22 hari kerja; minus 1 libur = 21 hari absen × Rp7.000.
        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect();
        $payroll = Payroll::where('karyawan_id', $employee->id)->first();
        $this->assertEquals(147000, (float) $payroll->potongan_absen);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
