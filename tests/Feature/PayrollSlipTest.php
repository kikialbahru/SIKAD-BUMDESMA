<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollSlipTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $code, string $email): User
    {
        $user = User::create([
            'name' => 'Karyawan '.$code,
            'email' => $email,
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create(['pengguna_id' => $user->id, 'kode_karyawan' => $code, 'aktif' => true]);

        return $user;
    }

    private function makePayroll(int $employeeId): Payroll
    {
        return Payroll::create([
            'karyawan_id' => $employeeId,
            'periode_mulai' => '2026-09-01',
            'periode_selesai' => '2026-09-30',
            'gaji_pokok' => 2000000,
            'tunjangan_kehadiran' => 300000,
            'total_reward' => 100000,
            'potongan_telat' => 5000,
            'potongan_absen' => 0,
            'total_hukuman' => 0,
            'gaji_bersih' => 2395000,
            'status' => 'draft',
        ]);
    }

    public function test_owner_can_view_own_slip(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->makeEmployee('KRY-SLIP', 'kry-slip@bumdesma.test');
        $payroll = $this->makePayroll($user->employee->id);

        $this->actingAs($user)->get(route('payroll.show', $payroll))
            ->assertOk()
            ->assertSee('2.395.000');
    }

    public function test_non_owner_cannot_view_slip(): void
    {
        $user = $this->makeEmployee('KRY-SLIP', 'kry-slip@bumdesma.test');
        $other = $this->makeEmployee('KRY-OTHER', 'kry-other@bumdesma.test');
        $payroll = $this->makePayroll($user->employee->id);

        $this->actingAs($other)->get(route('payroll.show', $payroll))->assertForbidden();
        $this->actingAs($other)->get(route('payroll.pdf', $payroll))->assertForbidden();
    }

    public function test_admin_can_view_any_slip_and_pdf(): void
    {
        $user = $this->makeEmployee('KRY-SLIP', 'kry-slip@bumdesma.test');
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-slip@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
        $payroll = $this->makePayroll($user->employee->id);

        $this->actingAs($admin)->get(route('payroll.show', $payroll))->assertOk();

        $response = $this->actingAs($admin)->get(route('payroll.pdf', $payroll));
        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('slip-gaji-KRY-SLIP-2026-09.pdf', $response->headers->get('Content-Disposition'));
        // Isi response harus byte PDF yang valid dan tidak kosong.
        $content = $response->getContent();
        $this->assertGreaterThan(1000, strlen($content));
        $this->assertStringStartsWith('%PDF', $content);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
