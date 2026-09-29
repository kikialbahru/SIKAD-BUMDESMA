<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(string $email = 'kry-901@bumdesma.test'): User
    {
        $user = User::create([
            'name' => 'Karyawan Tes',
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

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 640, 480);
    }

    public function test_check_in_requires_photo(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'lintang' => -6.9,
            'bujur' => 109.1,
            'akurasi' => 10,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('foto');
    }

    public function test_check_in_stores_photo_and_gps_with_server_time(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'foto' => $this->photo(),
            'lintang' => -6.9,
            'bujur' => 109.1,
            'akurasi' => 10,
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'type' => 'masuk']);
        $attendance = Attendance::first();
        $this->assertNotNull($attendance->waktu_masuk);
        $this->assertEquals('2026-09-10 08:00:00', $attendance->waktu_masuk->format('Y-m-d H:i:s'));
        $this->assertEquals('present', $attendance->status);
        Storage::disk('public')->assertExists($attendance->foto_masuk);
        $this->assertStringStartsWith('attendance/check-in/2026/09/', $attendance->foto_masuk);
        $this->assertEquals(-6.9, (float) $attendance->lintang_masuk);
        $this->assertEquals(109.1, (float) $attendance->bujur_masuk);
        $this->assertEquals(10, $attendance->akurasi_masuk);
    }

    public function test_duplicate_check_in_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $payload = ['foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 10];
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)->assertOk();
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Absensi masuk hari ini sudah tercatat.']);
    }

    public function test_check_in_outside_enforced_radius_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        AttendanceLocation::create([
            'nama' => 'Kantor', 'lintang' => -6.9, 'bujur' => 109.1,
            'radius_meter' => 100, 'maks_akurasi_meter' => 100,
            'wajib_radius' => true, 'aktif' => true,
        ]);
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'foto' => $this->photo(),
            'lintang' => -6.0, // ~100 km dari kantor
            'bujur' => 109.1,
            'akurasi' => 10,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('luar area absensi', $response->json('message'));
        $this->assertNull(Attendance::first()?->waktu_masuk);
    }

    public function test_check_in_poor_accuracy_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        AttendanceLocation::create([
            'nama' => 'Kantor', 'lintang' => -6.9, 'bujur' => 109.1,
            'radius_meter' => 100, 'maks_akurasi_meter' => 100,
            'wajib_radius' => true, 'aktif' => true,
        ]);
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'foto' => $this->photo(),
            'lintang' => -6.9,
            'bujur' => 109.1,
            'akurasi' => 500,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('belum cukup akurat', $response->json('message'));
    }

    public function test_check_out_requires_check_in_and_evidence_then_succeeds(): void
    {
        Storage::fake('public');
        $user = $this->employeeUser();

        Carbon::setTestNow('2026-09-10 15:05:00');
        $this->actingAs($user)->postJson('/attendance/check-out')
            ->assertStatus(422);

        Carbon::setTestNow('2026-09-10 08:02:00');
        $this->actingAs($user)->postJson('/attendance/check-in', [
            'foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 10,
        ])->assertOk();

        Carbon::setTestNow('2026-09-10 15:05:00');
        $response = $this->actingAs($user)->postJson('/attendance/check-out', [
            'foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 12,
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'type' => 'pulang']);
        $attendance = Attendance::first();
        $this->assertEquals('2026-09-10 15:05:00', $attendance->waktu_pulang->format('Y-m-d H:i:s'));
        Storage::disk('public')->assertExists($attendance->foto_pulang);
        $this->assertStringStartsWith('attendance/check-out/2026/09/', $attendance->foto_pulang);

        $this->actingAs($user)->postJson('/attendance/check-out', [
            'foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 12,
        ])->assertStatus(422);
    }

    public function test_employee_cannot_view_other_attendance_but_admin_can(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $owner = $this->employeeUser('kry-902@bumdesma.test');
        $other = $this->employeeUser('kry-903@bumdesma.test');
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-tes@bumdesma.test', 'role' => 'admin', 'password' => Hash::make('password')]);

        $this->actingAs($owner)->postJson('/attendance/check-in', [
            'foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 10,
        ])->assertOk();
        $attendance = Attendance::first();

        $this->actingAs($other)->get(route('attendance.show', $attendance))->assertForbidden();
        $this->actingAs($owner)->get(route('attendance.show', $attendance))->assertOk();
        $this->actingAs($admin)->get(route('attendance.show', $attendance))->assertOk();
    }

    public function test_admin_cannot_check_in(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-tes2@bumdesma.test', 'role' => 'admin', 'password' => Hash::make('password')]);

        $this->actingAs($admin)->postJson('/attendance/check-in', [
            'foto' => $this->photo(), 'lintang' => -6.9, 'bujur' => 109.1, 'akurasi' => 10,
        ])->assertForbidden();
    }

    public function test_checkout_from_field_allowed_while_checkin_enforced(): void
    {
        Storage::fake('public');
        AttendanceLocation::create([
            'nama' => 'Kantor', 'lintang' => -6.926433, 'bujur' => 109.185844,
            'radius_meter' => 100, 'maks_akurasi_meter' => 500,
            'wajib_radius' => true, 'wajib_radius_pulang' => false, 'aktif' => true,
        ]);
        $user = $this->employeeUser();

        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->actingAs($user)->postJson('/attendance/check-in', [
            'foto' => $this->photo(), 'lintang' => -6.926433, 'bujur' => 109.185844, 'akurasi' => 10,
        ])->assertOk();

        // Pulang dari lapangan (±100 km) tetap bisa, lokasi tercatat tanpa penegakan.
        Carbon::setTestNow('2026-09-10 15:05:00');
        $response = $this->actingAs($user)->postJson('/attendance/check-out', [
            'foto' => $this->photo(), 'lintang' => -6.0, 'bujur' => 109.1, 'akurasi' => 20,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $attendance = Attendance::first();
        $this->assertNotNull($attendance->waktu_pulang);
        $this->assertNull($attendance->lokasi_pulang_valid);
        $this->assertNotNull($attendance->lintang_pulang);
        $this->assertGreaterThan(100, $attendance->jarak_pulang_meter);
    }

    public function test_employee_dashboard_renders_capture_modals(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('checkInModal', false);
        $response->assertSee('checkOutModal', false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
