<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    public function index()
    {
        return view('attendance.settings', [
            'settings' => AttendanceSetting::current(),
            'title' => 'Pengaturan Absensi',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'different:jam_masuk'],
            'toleransi_telat_menit' => ['required', 'integer', 'min:0', 'max:180'],
            'mulai_absen_pulang' => ['required', 'date_format:H:i'],
            'akhir_absen_pulang' => ['required', 'date_format:H:i', 'different:mulai_absen_pulang'],
            'blok_menit_poin' => ['required', 'integer', 'min:1', 'max:240'],
            'poin_per_blok' => ['required', 'integer', 'min:0', 'max:100'],
        ]) + ['poin_otomatis_aktif' => $request->boolean('poin_otomatis_aktif')];

        AttendanceSetting::current()->update($data);

        return back()->with('success', 'Pengaturan absensi berhasil disimpan.');
    }
}
