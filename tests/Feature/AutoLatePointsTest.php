<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Punishment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutoLatePointsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Poin',
            'email' => 'admin-poin@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    private function employeeUser(string $email = 'kry-poin@bumdesma.test'): User
    {
        $user = User::create([
            'name' => 'Karyawan Poin',
            'email' => $email,
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'pengguna_id' => $user->id,
            'kode_karyawan' => 'KRY-'.substr(str_replace(['@', '.', '-'], '', $email), 0, 10),
            'aktif' => true,
        ]);

        return $user;
    }

    private function lateAttendance(Employee $employee, string $date, int $lateMinutes): void
    {
        Attendance::create([
            'karyawan_id' => $employee->id,
            'tanggal' => $date,
            'waktu_masuk' => $date.' 08:'.str_pad((string) min(59, $lateMinutes), 2, '0', STR_PAD_LEFT).':00',
            'menit_telat' => $lateMinutes,
            'status' => 'late',
        ]);
    }

    public function test_generate_creates_points_from_total_late(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $this->lateAttendance($employee, '2026-09-10', 20);
        $this->lateAttendance($employee, '2026-09-11', 10);

        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])
            ->assertRedirect();

        // Total 30 menit, aturan default 15 menit = 1 poin → 2 poin.
        $punishment = Punishment::first();
        $this->assertNotNull($punishment);
        $this->assertEquals($employee->id, $punishment->karyawan_id);
        $this->assertEquals('points_deduction', $punishment->jenis);
        $this->assertEquals(2, $punishment->poin);
        $this->assertEquals(0, (float) $punishment->nominal);
        $this->assertTrue($punishment->otomatis);
        $this->assertEquals('2026-09', $punishment->dijatuhkan_pada->format('Y-m'));
    }

    public function test_regenerate_replaces_previous_auto_rows(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $this->lateAttendance($employee, '2026-09-10', 20);

        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();
        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();
        $this->assertEquals(1, Punishment::count());

        $this->lateAttendance($employee, '2026-09-11', 10);
        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();

        $this->assertEquals(1, Punishment::count());
        $this->assertEquals(2, Punishment::first()->poin);
    }

    public function test_manual_punishments_are_untouched(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $this->lateAttendance($employee, '2026-09-10', 20);
        Punishment::create([
            'karyawan_id' => $employee->id,
            'jenis' => 'warning',
            'nominal' => 0,
            'judul' => 'Teguran manual',
            'dijatuhkan_pada' => '2026-09-05',
        ]);

        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();

        $this->assertEquals(2, Punishment::count());
        $this->assertEquals(1, Punishment::where('otomatis', false)->count());
    }

    public function test_custom_rule_is_respected(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        AttendanceSetting::current()->update([
            'blok_menit_poin' => 10,
            'poin_per_blok' => 2,
        ]);
        $this->lateAttendance($employee, '2026-09-10', 30);

        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();

        // 30 menit / 10 × 2 = 6 poin.
        $this->assertEquals(6, Punishment::first()->poin);
    }

    public function test_disabled_setting_rejects_generate(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        AttendanceSetting::current()->update(['poin_otomatis_aktif' => false]);
        $this->lateAttendance($employee, '2026-09-10', 20);

        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(0, Punishment::count());
    }

    public function test_employee_cannot_generate(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertForbidden();
    }

    public function test_recognition_page_shows_auto_badge_and_rule(): void
    {
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $this->lateAttendance($employee, '2026-09-10', 20);
        $this->actingAs($admin)->post(route('recognition.auto-points'), ['month' => '2026-09'])->assertRedirect();

        $this->actingAs($admin)->get(route('recognition.index'))
            ->assertOk()
            ->assertSee('Otomatis')
            ->assertSee('2 poin')
            ->assertSee('Generate');
    }
}
