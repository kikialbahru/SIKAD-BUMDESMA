<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestRuleTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $user = User::create([
            'name' => 'Karyawan Cuti',
            'email' => 'kry-cuti@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'pengguna_id' => $user->id,
            'kode_karyawan' => 'KRY-CUTI',
            'aktif' => true,
        ]);

        return $user;
    }

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Cuti',
            'email' => 'admin-cuti@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_leave_requires_seven_days_notice(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        // H+3 ditolak.
        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'leave',
            'tanggal_mulai' => '2026-09-19',
            'tanggal_selesai' => '2026-09-20',
            'alasan' => 'Liburan.',
        ])->assertSessionHasErrors('tanggal_mulai');
        $this->assertEquals(0, LeaveRequest::count());

        // Tepat H+7 diterima.
        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'leave',
            'tanggal_mulai' => '2026-09-23',
            'tanggal_selesai' => '2026-09-24',
            'alasan' => 'Liburan.',
        ])->assertRedirect();
        $this->assertEquals(1, LeaveRequest::count());
    }

    public function test_permission_type_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'permission',
            'tanggal_mulai' => '2026-09-23',
            'tanggal_selesai' => '2026-09-23',
            'alasan' => 'Urusan keluarga.',
        ])->assertSessionHasErrors('jenis');
        $this->assertEquals(0, LeaveRequest::count());
    }

    public function test_sick_is_exempt_from_notice_rule(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();
        $this->assertEquals(1, LeaveRequest::count());
    }

    public function test_owner_can_cancel_pending_request_with_attachment(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        Storage::fake('local');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
            'lampiran' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $request = LeaveRequest::first();
        $this->assertNotNull($request->jalur_lampiran);
        Storage::disk('local')->assertExists($request->jalur_lampiran);

        $this->actingAs($user)->delete(route('leave.destroy', $request))
            ->assertRedirect(route('leave.index'));
        $this->assertEquals(0, LeaveRequest::count());
        Storage::disk('local')->assertMissing($request->jalur_lampiran);
    }

    public function test_owner_cannot_cancel_processed_request(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();
        $admin = $this->adminUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $request = LeaveRequest::first();
        $this->actingAs($admin)->patch(route('leave.review', $request), ['status' => 'approved'])
            ->assertRedirect();

        $this->actingAs($user)->delete(route('leave.destroy', $request))->assertStatus(422);
        $this->assertEquals(1, LeaveRequest::count());
    }

    public function test_non_owner_cannot_cancel_request(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $other = User::create([
            'name' => 'Karyawan Lain',
            'email' => 'kry-lain@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create(['pengguna_id' => $other->id, 'kode_karyawan' => 'KRY-LAIN', 'aktif' => true]);

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $request = LeaveRequest::first();
        $this->actingAs($other)->delete(route('leave.destroy', $request))->assertForbidden();
        $this->assertEquals(1, LeaveRequest::count());
    }

    public function test_owner_can_update_pending_request(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $request = LeaveRequest::first();

        $this->actingAs($user)->put(route('leave.update', $request), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-18',
            'alasan' => 'Demam + kontrol dokter.',
        ])->assertRedirect(route('leave.show', $request));

        $this->assertEquals(2, $request->fresh()->jumlah_hari);
        $this->assertEquals('Demam + kontrol dokter.', $request->fresh()->alasan);
        $this->assertEquals('pending', $request->fresh()->status);
    }

    public function test_update_enforces_seven_day_rule_for_leave(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $request = LeaveRequest::first();

        // Ubah jenis menjadi cuti dengan tanggal mulai H+3 → ditolak.
        $this->actingAs($user)->put(route('leave.update', $request), [
            'jenis' => 'leave',
            'tanggal_mulai' => '2026-09-19',
            'tanggal_selesai' => '2026-09-20',
            'alasan' => 'Liburan.',
        ])->assertSessionHasErrors('tanggal_mulai');
        $this->assertEquals('sick', $request->fresh()->jenis);
    }

    public function test_owner_cannot_update_processed_request(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();
        $admin = $this->adminUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-17',
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $request = LeaveRequest::first();
        $this->actingAs($admin)->patch(route('leave.review', $request), ['status' => 'approved'])
            ->assertRedirect();

        $this->actingAs($user)->put(route('leave.update', $request), [
            'jenis' => 'sick',
            'tanggal_mulai' => '2026-09-17',
            'tanggal_selesai' => '2026-09-18',
            'alasan' => 'Berubah pikiran.',
        ])->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
