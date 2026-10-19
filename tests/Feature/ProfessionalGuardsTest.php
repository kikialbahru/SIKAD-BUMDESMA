<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfessionalGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Guard',
            'email' => 'admin-guard@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    private function employeeUser(string $code = 'KRY-GRD'): User
    {
        $user = User::create([
            'name' => 'Karyawan '.$code,
            'email' => strtolower($code).'@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'pengguna_id' => $user->id,
            'kode_karyawan' => $code,
            'aktif' => true,
        ]);

        return $user;
    }

    public function test_generate_skips_verified_payroll_unless_forced(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employeeId = $this->employeeUser()->employee->id;

        Attendance::create([
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-10',
            'menit_telat' => 10,
            'status' => 'late',
        ]);

        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect();
        $payroll = Payroll::where('karyawan_id', $employeeId)->first();
        $this->assertEquals(10000, (float) $payroll->potongan_telat);
        $payroll->update(['status' => 'verified']);

        // Tambah keterlambatan baru, generate ulang tanpa force: dilewati.
        Attendance::create([
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-11',
            'menit_telat' => 5,
            'status' => 'late',
        ]);
        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals(10000, (float) $payroll->fresh()->potongan_telat);
        $this->assertEquals('verified', $payroll->fresh()->status);

        // Dengan force: dihitung ulang dan kembali ke draft untuk verifikasi ulang.
        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09', 'force' => '1'])
            ->assertRedirect();
        $this->assertEquals(15000, (float) $payroll->fresh()->potongan_telat);
        $this->assertEquals('draft', $payroll->fresh()->status);
    }

    public function test_paid_payroll_cannot_return_directly_to_draft(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employeeId = $this->employeeUser()->employee->id;

        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect();
        $payroll = Payroll::where('karyawan_id', $employeeId)->first();
        $payroll->update(['status' => 'paid']);

        $this->actingAs($admin)->patch(route('payroll.status', $payroll), ['status' => 'draft'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertEquals('paid', $payroll->fresh()->status);

        $this->actingAs($admin)->patch(route('payroll.status', $payroll), ['status' => 'verified'])
            ->assertRedirect();
        $this->assertEquals('verified', $payroll->fresh()->status);
    }

    public function test_correction_and_leave_review_write_activity_logs(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employeeId = $this->employeeUser()->employee->id;

        $this->actingAs($admin)->post(route('attendance.corrections.store'), [
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-15',
            'status' => 'sick',
            'catatan' => 'Sakit tanpa surat.',
        ])->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'koreksi.create',
            'user_id' => $admin->id,
        ]);

        $leave = LeaveRequest::create([
            'karyawan_id' => $employeeId,
            'jenis' => 'leave',
            'tanggal_mulai' => '2026-09-20',
            'tanggal_selesai' => '2026-09-20',
            'jumlah_hari' => 1,
            'alasan' => 'Acara keluarga.',
            'status' => 'pending',
        ]);
        $this->actingAs($admin)->patch(route('leave.review', $leave), ['status' => 'approved'])
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'cuti.review',
            'user_id' => $admin->id,
        ]);
    }

    public function test_anomaly_flags_highlight_suspicious_attendance(): void
    {
        $employeeId = $this->employeeUser()->employee->id;

        $suspicious = Attendance::create([
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-10',
            'waktu_masuk' => '2026-09-10 08:30:00',
            'status' => 'present',
            'akurasi_masuk' => 800,
            'lokasi_masuk_valid' => false,
        ]);
        $this->assertNotEmpty($suspicious->anomalyFlags());

        $normal = Attendance::create([
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-11',
            'waktu_masuk' => '2026-09-11 07:55:00',
            'status' => 'present',
            'foto_masuk' => 'attendance/check-in/2026/09/selfie.jpg',
            'akurasi_masuk' => 25,
            'lokasi_masuk_valid' => true,
        ]);
        $this->assertEmpty($normal->anomalyFlags());

        $response = $this->actingAs($this->adminUser())->get(route('attendance.show', $suspicious));
        $response->assertOk()->assertSee('Perlu perhatian');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
