<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\VisitAttendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(string $code = 'KRY-VST'): User
    {
        $user = User::create([
            'name' => 'Karyawan '.$code,
            'email' => strtolower($code).'@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create(['pengguna_id' => $user->id, 'kode_karyawan' => $code, 'aktif' => true]);

        return $user;
    }

    private function makeVisit(int $employeeId, string $date, string $title = 'Kunjungan'): VisitAttendance
    {
        return VisitAttendance::create([
            'karyawan_id' => $employeeId,
            'judul' => $title,
            'tanggal' => $date,
            'foto' => 'visit-attendances/2026/09/dummy.jpg',
            'lintang' => -6.926433,
            'bujur' => 109.185844,
        ]);
    }

    public function test_owner_can_update_visit(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();
        $visit = $this->makeVisit($user->employee->id, '2026-09-15');

        $this->actingAs($user)->put(route('visits.update', $visit), [
            'judul' => 'Kunjungan revisi',
            'deskripsi' => 'Bertemu perangkat desa.',
            'tanggal' => '2026-09-16',
        ])->assertRedirect(route('visits.show', $visit));

        $fresh = $visit->fresh();
        $this->assertEquals('Kunjungan revisi', $fresh->judul);
        $this->assertEquals('Bertemu perangkat desa.', $fresh->deskripsi);
        $this->assertEquals('2026-09-16', $fresh->tanggal->toDateString());
        // Bukti GPS tidak berubah.
        $this->assertEquals(-6.926433, (float) $fresh->lintang);
    }

    public function test_update_rejects_future_date(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();
        $visit = $this->makeVisit($user->employee->id, '2026-09-15');

        $this->actingAs($user)->put(route('visits.update', $visit), [
            'judul' => 'Kunjungan',
            'tanggal' => '2026-09-17',
        ])->assertSessionHasErrors(['tanggal' => 'Tanggal kunjungan tidak boleh melebihi hari ini.']);
    }

    public function test_update_respects_daily_cap_when_moving_date(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();
        for ($i = 0; $i < VisitAttendance::MAX_PER_DAY; $i++) {
            $this->makeVisit($user->employee->id, '2026-09-16', 'Penuh '.$i);
        }
        $visit = $this->makeVisit($user->employee->id, '2026-09-15');

        $this->actingAs($user)->put(route('visits.update', $visit), [
            'judul' => 'Pindah',
            'tanggal' => '2026-09-16',
        ])->assertSessionHasErrors('tanggal');
        $this->assertEquals('2026-09-15', $visit->fresh()->tanggal->toDateString());
    }

    public function test_non_owner_cannot_update_visit(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser('KRY-VST');
        $other = $this->employeeUser('KRY-OTHER');
        $visit = $this->makeVisit($user->employee->id, '2026-09-15');

        $this->actingAs($other)->put(route('visits.update', $visit), [
            'judul' => 'Diubah orang lain',
            'tanggal' => '2026-09-15',
        ])->assertForbidden();
    }

    public function test_photo_replace_deletes_old_file(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        Storage::fake('public');
        $user = $this->employeeUser();
        $oldPath = UploadedFile::fake()->image('lama.jpg')->store('visit-attendances/2026/09', 'public');
        $visit = $this->makeVisit($user->employee->id, '2026-09-15');
        $visit->update(['foto' => $oldPath]);

        $this->actingAs($user)->put(route('visits.update', $visit), [
            'judul' => 'Kunjungan',
            'tanggal' => '2026-09-15',
            'foto' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($visit->fresh()->foto);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
