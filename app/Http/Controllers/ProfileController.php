<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless($employee || $user->isAdmin(), 404, 'Data karyawan belum terhubung ke akun ini.');

        return view('profile.index', [
            'user' => $user,
            'employee' => $employee?->load('user', 'position', 'workSchedule'),
            'photoCooldown' => $employee ? $this->photoCooldown($employee) : null,
            'title' => 'Profil Saya',
        ]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless($employee || $user->isAdmin(), 404, 'Data karyawan belum terhubung ke akun ini.');

        return view('profile.edit', [
            'user' => $user,
            'employee' => $employee?->load('user', 'position', 'workSchedule'),
            'photoCooldown' => $employee ? $this->photoCooldown($employee) : null,
            'title' => 'Edit Profil',
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless($employee || $user->isAdmin(), 404, 'Data karyawan belum terhubung ke akun ini.');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'telepon' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($data, $user, $employee) {
            $user->update(['name' => $data['nama'], 'email' => $data['email']]);
            if ($employee) {
                $employee->update(['nama' => $data['nama'], 'email' => $data['email'], 'telepon' => $data['telepon']]);
            }
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Akun administrator tidak memiliki foto profil karyawan.');

        if ($cooldown = $this->photoCooldown($employee)) {
            return back()->with('error', 'Foto profil hanya dapat diganti 1 minggu sekali. Dapat diganti lagi pada '.$cooldown['available_at']->translatedFormat('d F Y').' ('.$cooldown['days_left'].' hari lagi).');
        }

        if ($request->boolean('hapus_foto')) {
            if ($employee->foto) {
                Storage::disk('public')->delete($employee->foto);
                $employee->update(['foto' => null, 'foto_diubah_pada' => now()]);
            }

            return back()->with('success', 'Foto profil berhasil dihapus.');
        }

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        if ($employee->foto) {
            Storage::disk('public')->delete($employee->foto);
        }
        $employee->update(['foto' => $request->file('foto')->store('employee-photos', 'public'), 'foto_diubah_pada' => now()]);

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru tidak boleh sama dengan password saat ini.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    /**
     * Cooldown ganti foto: minimal 1 minggu sejak perubahan terakhir.
     * Return null jika sudah boleh ganti.
     */
    private function photoCooldown($employee): ?array
    {
        if (! $employee->foto_diubah_pada) {
            return null;
        }
        $availableAt = $employee->foto_diubah_pada->copy()->addWeek();
        if (! now()->lt($availableAt)) {
            return null;
        }

        return [
            'available_at' => $availableAt,
            'days_left' => max(1, (int) ceil(now()->floatDiffInDays($availableAt))),
        ];
    }
}
