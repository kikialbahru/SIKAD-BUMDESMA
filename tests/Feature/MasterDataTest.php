<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Master',
            'email' => 'admin-master@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    private function employeeUser(string $code = 'KRY-MST', array $extra = []): User
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
        ] + $extra);

        return $user;
    }

    public function test_admin_can_crud_position(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('positions.store'), [
            'nama' => 'Bendahara',
            'deskripsi' => 'Urus keuangan.',
        ])->assertRedirect();
        $this->assertDatabaseHas('jabatan', ['nama' => 'Bendahara']);

        $position = Position::where('nama', 'Bendahara')->first();
        $this->actingAs($admin)->put(route('positions.update', $position), [
            'nama' => 'Bendahara Umum',
            'deskripsi' => null,
        ])->assertRedirect();
        $this->assertDatabaseHas('jabatan', ['nama' => 'Bendahara Umum']);

        $this->actingAs($admin)->delete(route('positions.destroy', $position))->assertRedirect();
        $this->assertDatabaseMissing('jabatan', ['nama' => 'Bendahara Umum']);
    }

    public function test_cannot_delete_position_in_use(): void
    {
        $admin = $this->adminUser();
        $position = Position::create(['nama' => 'Staff']);
        $this->employeeUser('KRY-JAB', ['jabatan_id' => $position->id]);

        $this->actingAs($admin)->delete(route('positions.destroy', $position))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('jabatan', ['nama' => 'Staff']);
    }

    public function test_non_admin_blocked_from_master_data(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->get(route('positions.index'))->assertForbidden();
        $this->actingAs($user)->post(route('positions.store'), ['nama' => 'X'])->assertForbidden();
        $this->actingAs($user)->get(route('work-schedules.index'))->assertForbidden();
        $this->actingAs($user)->get(route('salary-components.index'))->assertForbidden();
    }

    public function test_schedule_validation_rejects_bad_times_and_empty_days(): void
    {
        $admin = $this->adminUser();

        // Jam pulang sebelum jam masuk ditolak.
        $this->actingAs($admin)->post(route('work-schedules.store'), [
            'nama' => 'Shift Rusak',
            'jam_masuk' => '15:00',
            'jam_pulang' => '08:00',
            'toleransi_telat_menit' => 10,
            'hari_kerja' => ['monday'],
        ])->assertSessionHasErrors('jam_pulang');

        // Tanpa hari kerja ditolak.
        $this->actingAs($admin)->post(route('work-schedules.store'), [
            'nama' => 'Shift Kosong',
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 10,
            'hari_kerja' => [],
        ])->assertSessionHasErrors('hari_kerja');

        $this->assertEquals(0, WorkSchedule::count());
    }

    public function test_cannot_deactivate_or_delete_last_active_schedule(): void
    {
        $admin = $this->adminUser();
        $schedule = WorkSchedule::create([
            'nama' => 'Reguler',
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 15,
            'hari_kerja' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'aktif' => true,
        ]);

        $payload = [
            'nama' => 'Reguler',
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 15,
            'hari_kerja' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            // tanpa checkbox aktif = nonaktif
        ];

        $this->actingAs($admin)->put(route('work-schedules.update', $schedule), $payload)
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($schedule->fresh()->aktif);

        $this->actingAs($admin)->delete(route('work-schedules.destroy', $schedule))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('jadwal_kerja', ['nama' => 'Reguler']);
    }

    public function test_cannot_delete_schedule_in_use(): void
    {
        $admin = $this->adminUser();
        WorkSchedule::create([
            'nama' => 'Cadangan',
            'jam_masuk' => '08:00',
            'jam_pulang' => '12:00',
            'toleransi_telat_menit' => 5,
            'hari_kerja' => ['saturday'],
            'aktif' => true,
        ]);
        $used = WorkSchedule::create([
            'nama' => 'Dipakai',
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 15,
            'hari_kerja' => ['monday'],
            'aktif' => true,
        ]);
        $this->employeeUser('KRY-JDW', ['jadwal_kerja_id' => $used->id]);

        $this->actingAs($admin)->delete(route('work-schedules.destroy', $used))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('jadwal_kerja', ['nama' => 'Dipakai']);
    }

    public function test_salary_component_unique_per_employee_and_type(): void
    {
        $admin = $this->adminUser();
        $user = $this->employeeUser();
        $employeeId = $user->employee->id;

        $this->actingAs($admin)->post(route('salary-components.store'), [
            'karyawan_id' => $employeeId,
            'jenis' => 'late_deduction',
            'nama' => 'Tarif telat',
            'nominal' => 2000,
            'aktif' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('komponen_gaji', ['karyawan_id' => $employeeId, 'jenis' => 'late_deduction']);

        // Duplikat jenis untuk karyawan sama ditolak.
        $this->actingAs($admin)->post(route('salary-components.store'), [
            'karyawan_id' => $employeeId,
            'jenis' => 'late_deduction',
            'nama' => 'Tarif telat 2',
            'nominal' => 3000,
        ])->assertSessionHasErrors('jenis');
        $this->assertEquals(1, SalaryComponent::where('karyawan_id', $employeeId)->count());
    }

    public function test_payroll_ignores_inactive_component_rate(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $user = $this->employeeUser();
        $employeeId = $user->employee->id;

        Attendance::create([
            'karyawan_id' => $employeeId,
            'tanggal' => '2026-09-10',
            'menit_telat' => 10,
            'status' => 'late',
        ]);
        SalaryComponent::create([
            'karyawan_id' => $employeeId,
            'jenis' => 'late_deduction',
            'nama' => 'Tarif telat',
            'nominal' => 5000,
            'aktif' => false,
        ]);

        // Nonaktif → pakai bawaan Rp1.000/mnt × 10 = Rp10.000.
        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect();
        $payroll = Payroll::where('karyawan_id', $employeeId)->first();
        $this->assertEquals(10000, (float) $payroll->potongan_telat);

        // Diaktifkan → Rp5.000/mnt × 10 = Rp50.000.
        SalaryComponent::where('karyawan_id', $employeeId)->update(['aktif' => true]);
        $this->actingAs($admin)->post(route('payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect();
        $this->assertEquals(50000, (float) $payroll->fresh()->potongan_telat);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
