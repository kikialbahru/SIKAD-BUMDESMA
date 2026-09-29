<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AttendanceLocationService;
use App\Services\CheckoutPolicy;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:present,late,leave,permission,sick,absent'],
            'status_pulang' => ['nullable', 'in:waiting,approved,rejected,checked_out'],
            'month' => ['nullable', 'date_format:Y-m'],
            'karyawan_id' => ['nullable', 'integer', 'exists:karyawan,id'],
        ]);
        if (! empty($filters['month'])) {
            $filters['date_from'] = date('Y-m-01', strtotime($filters['month'].'-01'));
            $filters['date_to'] = date('Y-m-t', strtotime($filters['month'].'-01'));
        } elseif (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = now()->copy()->startOfMonth()->toDateString();
            $filters['date_to'] = now()->copy()->endOfMonth()->toDateString();
        }
        $monthValue = substr($filters['date_from'], 0, 7);
        $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $monthLabel = fn (string $ym) => $monthNames[(int) substr($ym, 5, 2)].' '.substr($ym, 0, 4);
        $monthNav = [
            'value' => $monthValue,
            'label' => $monthLabel($monthValue),
            'prev' => date('Y-m', strtotime($monthValue.'-01 -1 month')),
            'prevLabel' => $monthLabel(date('Y-m', strtotime($monthValue.'-01 -1 month'))),
            'next' => date('Y-m', strtotime($monthValue.'-01 +1 month')),
            'nextLabel' => $monthLabel(date('Y-m', strtotime($monthValue.'-01 +1 month'))),
            'isCurrent' => $monthValue === now()->format('Y-m'),
        ];
        $query = Attendance::with('employee.user', 'employee.position')->latest('tanggal')->latest('waktu_masuk');

        if (! $request->user()->isAdmin()) {
            $query->where('karyawan_id', $request->user()->employee?->id ?? 0);
        }
        if (! empty($filters['search'])) {
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('kode_karyawan', 'like', '%'.$filters['search'].'%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$filters['search'].'%')));
        }
        $query->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date));
        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $query->when($filters['status_pulang'] ?? null, fn ($query, $status) => $query->where('status_pulang', $status));
        $query->when($filters['karyawan_id'] ?? null, fn ($query, $employeeId) => $query->where('karyawan_id', $employeeId));

        return view('attendance.index', [
            'attendances' => $query->paginate(15),
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('aktif', true)->orderBy('kode_karyawan')->get() : collect(),
            'filters' => $filters,
            'monthNav' => $monthNav,
            'settings' => AttendanceSetting::current(),
        ]);
    }

    public function show(Request $request, Attendance $attendance)
    {
        // Karyawan hanya boleh melihat absensinya sendiri; admin boleh semua.
        abort_unless(
            $request->user()->isAdmin() || $attendance->karyawan_id === $request->user()->employee?->id,
            403,
            'Anda tidak berhak melihat absensi ini.'
        );

        return view('attendance.show', [
            'attendance' => $attendance->load('employee.user', 'employee.position'),
            'office' => AttendanceLocation::active(),
            'title' => 'Detail Absensi',
        ]);
    }

    public function checkIn(Request $request)
    {
        abort_if($request->user()->isAdmin(), 403, 'Administrator tidak perlu melakukan absensi.');
        $employee = $request->user()->employee;
        abort_unless($employee?->aktif, 403, 'Akun karyawan belum terhubung.');

        $now = now();
        $onLeave = LeaveRequest::where('karyawan_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('tanggal_mulai', '<=', $now->toDateString())
            ->whereDate('tanggal_selesai', '>=', $now->toDateString())
            ->exists();
        if ($onLeave) {
            return $this->attendanceFailed($request, 'Anda memiliki pengajuan cuti/izin yang disetujui pada hari ini.');
        }
        $attendance = Attendance::firstOrCreate(['karyawan_id' => $employee->id, 'tanggal' => $now->toDateString()]);
        if ($attendance->waktu_masuk) {
            return $this->attendanceFailed($request, 'Absensi masuk hari ini sudah tercatat.');
        }

        $evidence = $this->validatedEvidence($request);
        if (! $evidence['ok']) {
            return $this->attendanceFailed($request, $evidence['message'], 422);
        }

        $lateMinutes = 0;
        $times = CheckoutPolicy::workTimes($employee, $now);
        $lateMinutes = max(0, $times['start']->diffInMinutes($now, false));

        $attendance->update([
            'waktu_masuk' => $now,
            'menit_telat' => $lateMinutes,
            'status' => $lateMinutes > 0 ? 'late' : 'present',
            'foto_masuk' => $evidence['foto'],
            'lintang_masuk' => $evidence['lintang'],
            'bujur_masuk' => $evidence['bujur'],
            'akurasi_masuk' => $evidence['akurasi'],
            'jarak_masuk_meter' => $evidence['jarak'],
            'lokasi_masuk_valid' => $evidence['valid'],
        ]);

        return $this->attendanceSuccess($request, $attendance->fresh(), 'masuk', 'Absensi masuk berhasil dicatat pada '.$now->format('H:i').' WIB.');
    }

    public function checkOut(Request $request)
    {
        abort_if($request->user()->isAdmin(), 403, 'Administrator tidak perlu melakukan absensi.');
        $employee = $request->user()->employee;
        $attendance = $employee?->attendances()->whereDate('tanggal', today())->first();
        abort_unless($attendance?->waktu_masuk, 422, 'Lakukan absensi masuk terlebih dahulu.');

        if ($attendance->waktu_pulang) {
            return $this->attendanceFailed($request, 'Absensi pulang hari ini sudah tercatat.');
        }

        $eligibility = CheckoutPolicy::checkoutEligibility($employee, $attendance);
        if (! $eligibility['allowed']) {
            return $this->attendanceFailed($request, $eligibility['reason'], 422);
        }

        $evidence = $this->validatedEvidence($request);
        if (! $evidence['ok']) {
            return $this->attendanceFailed($request, $evidence['message'], 422);
        }

        $now = now();
        $attendance->update([
            'waktu_pulang' => $now,
            'menit_kerja' => $attendance->waktu_masuk->diffInMinutes($now),
            'status_pulang' => 'checked_out',
            'foto_pulang' => $evidence['foto'],
            'lintang_pulang' => $evidence['lintang'],
            'bujur_pulang' => $evidence['bujur'],
            'akurasi_pulang' => $evidence['akurasi'],
            'jarak_pulang_meter' => $evidence['jarak'],
            'lokasi_pulang_valid' => $evidence['valid'],
        ]);

        return $this->attendanceSuccess($request, $attendance->fresh(), 'pulang', 'Absensi pulang berhasil dicatat pada '.$now->format('H:i').' WIB.');
    }

    /**
     * Validasi foto selfie + koordinat GPS, simpan foto ke Storage,
     * dan validasi radius di BACKEND. Waktu selalu memakai server (now()).
     */
    private function validatedEvidence(Request $request): array
    {
        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'lintang' => ['required', 'numeric', 'between:-90,90'],
            'bujur' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $latitude = (float) $data['lintang'];
        $longitude = (float) $data['bujur'];
        $accuracy = isset($data['akurasi']) ? (float) $data['akurasi'] : null;

        // Cek lokasi SEBELUM menyimpan foto agar tidak ada file yatim.
        // Absen pulang: catat-saja (boleh dari lapangan) kecuali admin mengaktifkan
        // penegakan radius pulang; absen masuk tetap wajib dalam radius kantor.
        $isCheckout = $request->route()->getName() === 'attendance.check-out';
        $office = AttendanceLocation::active();
        $enforce = (bool) ($office && ($isCheckout ? $office->wajib_radius_pulang : $office->wajib_radius));
        $check = AttendanceLocationService::check($latitude, $longitude, $accuracy, $enforce);
        if ($check['state'] === 'poor_accuracy' || $check['state'] === 'outside') {
            return ['ok' => false, 'message' => $check['message']];
        }

        $folder = ($request->route()->getName() === 'attendance.check-out' ? 'attendance/check-out/' : 'attendance/check-in/').now()->format('Y/m');
        $photo = $request->file('foto')->store($folder, 'public');

        return [
            'ok' => true,
            'foto' => $photo,
            'lintang' => $latitude,
            'bujur' => $longitude,
            'akurasi' => $accuracy !== null ? (int) round($accuracy) : null,
            'jarak' => $check['distance'],
            'valid' => $check['state'] === 'ok' ? true : ($check['state'] === 'unchecked' ? null : false),
            'location' => $check['location'],
        ];
    }

    private function attendanceFailed(Request $request, string $message, int $status = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message);
    }

    private function attendanceSuccess(Request $request, Attendance $attendance, string $type, string $message)
    {
        if ($request->wantsJson()) {
            $at = $type === 'pulang' ? $attendance->waktu_pulang : $attendance->waktu_masuk;

            return response()->json([
                'ok' => true,
                'message' => $message,
                'type' => $type,
                'attendance_id' => $attendance->id,
                'detail_url' => route('attendance.show', $attendance),
                'date' => $attendance->tanggal->translatedFormat('d F Y'),
                'time' => $at?->format('H:i:s').' WIB',
                'status' => $attendance->status,
                'foto_url' => asset('storage/'.($type === 'pulang' ? $attendance->foto_pulang : $attendance->foto_masuk)),
                'lintang' => $type === 'pulang' ? $attendance->lintang_pulang : $attendance->lintang_masuk,
                'bujur' => $type === 'pulang' ? $attendance->bujur_pulang : $attendance->bujur_masuk,
                'akurasi' => $type === 'pulang' ? $attendance->akurasi_pulang : $attendance->akurasi_masuk,
                'jarak' => $type === 'pulang' ? $attendance->jarak_pulang_meter : $attendance->jarak_masuk_meter,
            ]);
        }

        return back()->with('success', $message);
    }

    public function updateCheckoutApproval(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'type' => ['nullable', 'in:early,special'],
        ]);

        if ($attendance->waktu_pulang) {
            return back()->with('error', 'Karyawan sudah absen pulang, persetujuan tidak dapat diubah.');
        }

        if ($data['decision'] === 'approved') {
            $attendance->update([
                'status_pulang' => 'approved',
                'tipe_persetujuan_pulang' => $data['type'] ?? 'early',
                'disetujui_oleh' => $request->user()->id,
                'disetujui_pada' => now(),
            ]);

            return back()->with('success', 'Pulang lebih awal berhasil diizinkan untuk karyawan.');
        }

        $attendance->update([
            'status_pulang' => 'rejected',
            'tipe_persetujuan_pulang' => null,
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
        ]);

        return back()->with('success', 'Pengajuan pulang awal berhasil ditolak.');
    }
}
