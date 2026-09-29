<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceLocationController extends Controller
{
    public function index()
    {
        return view('attendance.locations', [
            'locations' => AttendanceLocation::orderBy('id')->get(),
            'active' => AttendanceLocation::active(),
            'title' => 'Lokasi Absensi',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['aktif'] = true;

        DB::transaction(function () use ($data) {
            AttendanceLocation::query()->update(['aktif' => false]);
            AttendanceLocation::create($data);
        });

        return back()->with('success', 'Lokasi absensi berhasil ditambahkan dan diaktifkan.');
    }

    public function update(Request $request, AttendanceLocation $location)
    {
        $location->update($this->validated($request));

        return back()->with('success', 'Lokasi absensi berhasil diperbarui.');
    }

    public function activate(AttendanceLocation $location)
    {
        DB::transaction(function () use ($location) {
            AttendanceLocation::query()->update(['aktif' => false]);
            $location->update(['aktif' => true]);
        });

        return back()->with('success', $location->nama.' kini menjadi lokasi absensi aktif.');
    }

    public function destroy(AttendanceLocation $location)
    {
        if (AttendanceLocation::count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya lokasi absensi.');
        }

        $wasActive = $location->aktif;
        $location->delete();

        if ($wasActive) {
            AttendanceLocation::orderBy('id')->first()?->update(['aktif' => true]);
        }

        return back()->with('success', 'Lokasi absensi berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'lintang' => ['required', 'numeric', 'between:-90,90'],
            'bujur' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:10', 'max:5000'],
            'maks_akurasi_meter' => ['required', 'integer', 'min:10', 'max:1000'],
            'wajib_radius' => ['nullable', 'boolean'],
            'wajib_radius_pulang' => ['nullable', 'boolean'],
        ]) + ['wajib_radius' => $request->boolean('wajib_radius'), 'wajib_radius_pulang' => $request->boolean('wajib_radius_pulang')];
    }
}
