<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    public function index()
    {
        $schedules = WorkSchedule::withCount(['employees as employees_count' => fn ($query) => $query->withTrashed()])
            ->orderBy('nama')
            ->get();

        return view('master-data.schedules', [
            'schedules' => $schedules,
            'days' => self::DAYS,
            'activeTab' => 'schedules',
            'title' => 'Master Data - Jadwal Kerja',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['aktif'] = $request->boolean('aktif');

        WorkSchedule::create($data);

        return back()->with('success', 'Jadwal kerja berhasil ditambahkan.');
    }

    public function update(Request $request, WorkSchedule $schedule)
    {
        $data = $this->validated($request, $schedule);
        $data['aktif'] = $request->boolean('aktif');

        if (! $data['aktif'] && ! WorkSchedule::where('aktif', true)->whereKeyNot($schedule->id)->exists()) {
            return back()->with('error', 'Tidak dapat menonaktifkan satu-satunya jadwal kerja yang aktif.');
        }

        $schedule->update($data);

        return back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $schedule)
    {
        if ($schedule->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Jadwal "'.$schedule->nama.'" masih dipakai karyawan dan tidak dapat dihapus. Pindahkan dulu karyawannya ke jadwal lain.');
        }

        if ($schedule->aktif && ! WorkSchedule::where('aktif', true)->whereKeyNot($schedule->id)->exists()) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya jadwal kerja yang aktif.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    private function validated(Request $request, ?WorkSchedule $schedule = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:jadwal_kerja,nama,'.($schedule?->id ?? 'NULL')],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'after:jam_masuk'],
            'toleransi_telat_menit' => ['required', 'integer', 'min:0', 'max:180'],
            'hari_kerja' => ['required', 'array', 'min:1'],
            'hari_kerja.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'aktif' => ['nullable', 'boolean'],
        ]);
    }
}
