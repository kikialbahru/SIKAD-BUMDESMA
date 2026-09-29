<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestReviewed;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $user = User::create([
            'name' => 'Karyawan Notif',
            'email' => 'kry-notif@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'pengguna_id' => $user->id,
            'kode_karyawan' => 'KRY-NOTIF',
            'nama' => 'Karyawan Notif',
            'aktif' => true,
        ]);

        return $user;
    }

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Notif',
            'email' => 'admin-notif@bumdesma.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_submit_notifies_admins(): void
    {
        $admin = $this->adminUser();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $this->assertCount(1, $admin->refresh()->notifications);
        $notif = $admin->refresh()->notifications->first();
        $this->assertEquals(LeaveRequestSubmitted::class, $notif->type);
        $this->assertStringContainsString('Karyawan Notif', $notif->data['body']);
        // Pengaju tidak menotifikasi dirinya sendiri.
        $this->assertCount(0, $user->refresh()->notifications);
    }

    public function test_review_notifies_employee(): void
    {
        $admin = $this->adminUser();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $leave = LeaveRequest::first();

        $this->actingAs($admin)->patch(route('leave.review', $leave), [
            'status' => 'approved',
        ])->assertRedirect();

        $notif = $user->refresh()->notifications->first();
        $this->assertNotNull($notif);
        $this->assertEquals(LeaveRequestReviewed::class, $notif->type);
        $this->assertStringContainsString('disetujui', $notif->data['title']);
    }

    public function test_read_redirects_and_marks_read(): void
    {
        $admin = $this->adminUser();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'jenis' => 'sick',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Demam.',
        ])->assertRedirect();

        $notif = $admin->refresh()->notifications->first();
        $leave = LeaveRequest::first();

        $this->actingAs($admin)->get(route('notifications.read', $notif->id))
            ->assertRedirect('/leave-requests/'.$leave->id);
        $this->assertNotNull($notif->refresh()->read_at);

        $this->actingAs($admin)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertEquals(0, $admin->refresh()->unreadNotifications()->count());
    }
}
