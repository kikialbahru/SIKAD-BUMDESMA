<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestReviewed;
use App\Notifications\LeaveRequestSubmitted;
use App\Services\ActivityLogger;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'in:leave,sick,duty'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = LeaveRequest::with('employee.user', 'employee.position', 'reviewer')->latest();
        if (! $request->user()->isAdmin()) {
            $query->where('karyawan_id', $request->user()->employee?->id ?? 0);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery
                ->where('kode_karyawan', 'like', '%'.$search.'%')
                ->orWhere('nama', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')));
        }
        $query->when($filters['jenis'] ?? null, fn ($query, $type) => $query->where('jenis', $type));
        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '1970-01-01';
            $to = $filters['date_to'] ?? '2999-12-31';
            $query->whereDate('tanggal_mulai', '<=', $to)->whereDate('tanggal_selesai', '>=', $from);
        }

        $stats = null;
        if ($request->user()->isAdmin()) {
            $stats = [
                'total' => LeaveRequest::count(),
                'pending' => LeaveRequest::where('status', 'pending')->count(),
                'approved' => LeaveRequest::where('status', 'approved')->count(),
                'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            ];
        }

        return view('leave.index', [
            'requests' => $query->paginate(10)->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        $leaveRequest->load('employee.user', 'employee.position', 'reviewer');

        if (! $request->user()->isAdmin()) {
            abort_if($leaveRequest->karyawan_id !== $request->user()->employee?->id, 403);
        }

        $history = LeaveRequest::where('karyawan_id', $leaveRequest->karyawan_id)
            ->where('id', '!=', $leaveRequest->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('leave.show', [
            'leaveRequest' => $leaveRequest,
            'history' => $history,
            'quota' => $leaveRequest->jenis === 'leave' && $leaveRequest->employee ? LeaveQuota::usage($leaveRequest->employee) : null,
            'title' => 'Detail Pengajuan Cuti & Sakit',
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->aktif, 403, 'Akun karyawan belum terhubung.');

        $data = $request->validate([
            'jenis' => ['required', 'in:leave,sick,duty'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);
        $data['karyawan_id'] = $employee->id;
        $data['jumlah_hari'] = CarbonPeriod::create($data['tanggal_mulai'], $data['tanggal_selesai'])->count();
        $data['jalur_lampiran'] = $request->file('lampiran')?->store('leave-attachments');

        // Cuti maksimal 3 hari berturut-turut dalam satu pengajuan.
        if ($data['jenis'] === 'leave' && $data['jumlah_hari'] > 3) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Pengajuan cuti maksimal 3 hari berturut-turut dalam satu pengajuan.',
            ]);
        }

        $leaveRequest = LeaveRequest::create($data);
        $leaveRequest->load('employee');

        // Notifikasi email bersifat pelengkap: kegagalan SMTP tidak boleh
        // menggagalkan pengajuan yang sudah tersimpan.
        try {
            Notification::send(User::where('role', 'admin')->get(), new LeaveRequestSubmitted($leaveRequest));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan.');
    }

    /**
     * Ubah pengajuan milik sendiri yang masih pending.
     * Aturan validasi sama dengan pengajuan baru (cuti boleh dadakan, maks 3 hari).
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->aktif, 403, 'Akun karyawan belum terhubung.');
        abort_if($leaveRequest->karyawan_id !== $employee->id, 403);

        $data = $request->validate([
            'jenis' => ['required', 'in:leave,sick,duty'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);
        $data['jumlah_hari'] = CarbonPeriod::create($data['tanggal_mulai'], $data['tanggal_selesai'])->count();

        // Cuti maksimal 3 hari berturut-turut dalam satu pengajuan (berlaku juga saat ubah).
        if ($data['jenis'] === 'leave' && $data['jumlah_hari'] > 3) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Pengajuan cuti maksimal 3 hari berturut-turut dalam satu pengajuan.',
            ]);
        }

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();
            abort_if($fresh->status !== 'pending', 422, 'Pengajuan sudah diproses admin dan tidak dapat diubah.');

            $lampiran = $fresh->jalur_lampiran;
            if ($request->hasFile('lampiran')) {
                if ($lampiran) {
                    Storage::disk('local')->delete($lampiran);
                }
                $lampiran = $request->file('lampiran')->store('leave-attachments');
            }

            $fresh->update([
                'jenis' => $data['jenis'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'jumlah_hari' => $data['jumlah_hari'],
                'alasan' => $data['alasan'],
                'jalur_lampiran' => $lampiran,
            ]);
        });

        return redirect()->route('leave.show', $leaveRequest)->with('success', 'Pengajuan berhasil diubah.');
    }

    /**
     * Batalkan (tarik kembali) pengajuan milik sendiri yang masih pending.
     * Baris dihapus permanen + file lampiran ikut dihapus.
     */
    public function destroy(Request $request, LeaveRequest $leaveRequest)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->aktif, 403, 'Akun karyawan belum terhubung.');
        abort_if($leaveRequest->karyawan_id !== $employee->id, 403);

        DB::transaction(function () use ($leaveRequest) {
            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();
            abort_if($fresh->status !== 'pending', 422, 'Pengajuan sudah diproses admin dan tidak dapat dibatalkan.');
            if ($fresh->jalur_lampiran) {
                Storage::disk('local')->delete($fresh->jalur_lampiran);
            }
            $fresh->delete();
        });

        return redirect()->route('leave.index')->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function attachment(Request $request, LeaveRequest $leaveRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($leaveRequest->karyawan_id !== $request->user()->employee?->id, 403);
        }
        abort_unless($leaveRequest->jalur_lampiran && Storage::disk('local')->exists($leaveRequest->jalur_lampiran), 404);

        return Storage::disk('local')->download($leaveRequest->jalur_lampiran);
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'catatan_ulasan' => ['nullable', 'string', 'max:2000'],
            'alasan_penolakan' => [Rule::requiredIf($request->input('status') === 'rejected'), 'nullable', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();
            abort_if($fresh->status !== 'pending', 422, 'Pengajuan sudah diproses sebelumnya.');

            if ($data['status'] === 'approved') {
                $employee = $fresh->employee;
                abort_unless($employee?->aktif, 422, 'Karyawan sudah tidak aktif.');

                if ($fresh->jenis === 'leave') {
                    if ($fresh->jumlah_hari > 3) {
                        abort(422, 'Pengajuan cuti maksimal 3 hari berturut-turut dalam satu pengajuan.');
                    }
                    $usage = LeaveQuota::usage($employee, (int) $fresh->tanggal_mulai->format('Y'));
                    if ($usage['remaining'] < $fresh->jumlah_hari) {
                        abort(422, 'Kuota cuti karyawan tidak mencukupi.');
                    }
                    $overlap = LeaveRequest::where('karyawan_id', $employee->id)
                        ->where('jenis', 'leave')
                        ->where('status', 'approved')
                        ->where('id', '!=', $fresh->id)
                        ->whereDate('tanggal_mulai', '<=', $fresh->tanggal_selesai->toDateString())
                        ->whereDate('tanggal_selesai', '>=', $fresh->tanggal_mulai->toDateString())
                        ->exists();
                    if ($overlap) {
                        abort(422, 'Terdapat cuti yang sudah disetujui pada periode tersebut.');
                    }
                }

                // Dinas keluar tidak memotong kuota cuti dan tidak dibatasi 3 hari,
                // cukup cegah double-booking dengan dinas yang sudah disetujui.
                if ($fresh->jenis === 'duty') {
                    $overlap = LeaveRequest::where('karyawan_id', $employee->id)
                        ->where('jenis', 'duty')
                        ->where('status', 'approved')
                        ->where('id', '!=', $fresh->id)
                        ->whereDate('tanggal_mulai', '<=', $fresh->tanggal_selesai->toDateString())
                        ->whereDate('tanggal_selesai', '>=', $fresh->tanggal_mulai->toDateString())
                        ->exists();
                    if ($overlap) {
                        abort(422, 'Terdapat dinas keluar yang sudah disetujui pada periode tersebut.');
                    }
                }
            }

            $fresh->update([
                'status' => $data['status'],
                'catatan_ulasan' => $data['catatan_ulasan'] ?? null,
                'alasan_penolakan' => $data['status'] === 'rejected' ? $data['alasan_penolakan'] : null,
                'diulas_oleh' => $request->user()->id,
                'diulas_pada' => now(),
            ]);

            if ($data['status'] === 'approved') {
                foreach (CarbonPeriod::create($fresh->tanggal_mulai, $fresh->tanggal_selesai) as $date) {
                    Attendance::updateOrCreate(
                        ['karyawan_id' => $fresh->karyawan_id, 'tanggal' => $date->toDateString()],
                        ['status' => in_array($fresh->jenis, ['leave', 'sick', 'duty'], true) ? $fresh->jenis : 'permission', 'catatan' => $fresh->alasan]
                    );
                }
            }
        });

        $leaveRequest->refresh()->load('employee.user');
        try {
            $leaveRequest->employee?->user?->notify(new LeaveRequestReviewed($leaveRequest));
        } catch (\Throwable $e) {
            report($e);
        }

        ActivityLogger::log('cuti.review', $leaveRequest, [
            'karyawan_id' => $leaveRequest->karyawan_id,
            'jenis' => $leaveRequest->jenis,
            'status' => $data['status'],
        ]);

        return back()->with('success', $data['status'] === 'approved' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.');
    }
}
