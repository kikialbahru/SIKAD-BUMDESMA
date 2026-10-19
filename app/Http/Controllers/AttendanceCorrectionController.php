<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\ActivityLogger;
use App\Services\CheckoutPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    public function create()
    {
        return view('attendance.corrections.create', [
            'employees' => $this->employees(),
            'title' => 'Input Absensi Manual',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $attendance = Attendance::create($this->attributes($data));

        ActivityLogger::log('koreksi.create', $attendance, [
            'karyawan_id' => $attendance->karyawan_id,
            'tanggal' => $attendance->tanggal->toDateString(),
            'status' => $attendance->status,
        ]);

        return redirect()->route('attendance.index')->with('success', 'Absensi manual berhasil dicatat.');
    }

    public function edit(Attendance $attendance)
    {
        return view('attendance.corrections.edit', [
            'attendance' => $attendance->load('employee.user'),
            'employees' => $this->employees(),
            'title' => 'Koreksi Absensi',
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $this->validated($request, $attendance);
        $attributes = $this->attributes($data);

        // Bukti foto/GPS asli tidak diubah; persetujuan pulang awal dihitung ulang
        // dari jam yang dikoreksi sehingga admin dapat menyetujui ulang bila perlu.
        $attributes['tipe_persetujuan_pulang'] = null;
        $attributes['disetujui_oleh'] = null;
        $attributes['disetujui_pada'] = null;

        $attendance->update($attributes);

        ActivityLogger::log('koreksi.update', $attendance, [
            'karyawan_id' => $attendance->karyawan_id,
            'tanggal' => $attendance->tanggal->toDateString(),
            'status' => $attendance->status,
        ]);

        return redirect()->route('attendance.index')->with('success', 'Koreksi absensi berhasil disimpan.');
    }

    /**
     * Jam masuk/pulang + status dihitung ulang dari jam kerja karyawan
     * (jadwal personal > pengaturan umum), sama seperti absen normal.
     */
    private function attributes(array $data): array
    {
        $employee = Employee::findOrFail($data['karyawan_id']);
        $date = Carbon::parse($data['tanggal'])->toDateString();
        $checkInAt = ! empty($data['jam_masuk']) ? Carbon::parse($date.' '.$data['jam_masuk']) : null;
        $checkOutAt = $checkInAt && ! empty($data['jam_pulang']) ? Carbon::parse($date.' '.$data['jam_pulang']) : null;

        if ($checkInAt) {
            $times = CheckoutPolicy::workTimes($employee, $checkInAt);
            $lateMinutes = CheckoutPolicy::lateMinutes($times['start'], $checkInAt, $times['tolerance']);

            return [
                'karyawan_id' => $employee->id,
                'tanggal' => $date,
                'waktu_masuk' => $checkInAt,
                'waktu_pulang' => $checkOutAt,
                'menit_telat' => $lateMinutes,
                'menit_kerja' => $checkOutAt ? $checkInAt->diffInMinutes($checkOutAt) : 0,
                'status' => $lateMinutes > 0 ? 'late' : 'present',
                'status_pulang' => $checkOutAt ? 'checked_out' : 'waiting',
                'catatan' => $data['catatan'] ?? null,
            ];
        }

        return [
            'karyawan_id' => $employee->id,
            'tanggal' => $date,
            'waktu_masuk' => null,
            'waktu_pulang' => null,
            'menit_telat' => 0,
            'menit_kerja' => 0,
            'status' => $data['status'],
            'status_pulang' => 'waiting',
            'catatan' => $data['catatan'] ?? null,
        ];
    }

    private function validated(Request $request, ?Attendance $attendance = null): array
    {
        $data = $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawan,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today', Rule::unique('kehadiran', 'tanggal')->where(fn ($query) => $query->where('karyawan_id', $request->input('karyawan_id')))->ignore($attendance?->id)],
            'jam_masuk' => ['nullable', 'date_format:H:i', 'required_with:jam_pulang'],
            'jam_pulang' => ['nullable', 'date_format:H:i', 'after:jam_masuk'],
            'status' => ['required', 'in:present,late,leave,permission,sick,duty,absent'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.unique' => 'Karyawan ini sudah memiliki data absensi pada tanggal tersebut — gunakan tombol koreksi (ikon pensil).',
            'jam_masuk.required_with' => 'Jam masuk wajib diisi bila jam pulang diisi.',
        ]);

        if (empty($data['jam_masuk']) && in_array($data['status'], ['present', 'late'], true)) {
            throw ValidationException::withMessages([
                'jam_masuk' => 'Isi jam masuk, atau pilih status Cuti/Izin/Sakit/Dinas/Alpa bila karyawan tidak hadir.',
            ]);
        }

        return $data;
    }

    private function employees()
    {
        return Employee::with('user')->orderBy('aktif', 'desc')->orderBy('kode_karyawan')->get();
    }
}
