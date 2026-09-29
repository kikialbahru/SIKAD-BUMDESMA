<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\VisitAttendance;
use App\Services\CheckoutPolicy;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $employee = auth()->user()->employee;
        $attendance = $employee?->attendances()->whereDate('tanggal', $today)->first();
        $monthStart = $today->copy()->startOfMonth();

        return view('dashboard.index', [
            'employee' => $employee,
            'attendance' => $attendance,
            'checkout' => ($employee && $attendance?->waktu_masuk) ? CheckoutPolicy::checkoutEligibility($employee, $attendance) : null,
            'times' => $employee ? CheckoutPolicy::workTimes($employee, $today) : null,
            'presentCount' => $employee?->attendances()->whereBetween('tanggal', [$monthStart, $today])->whereIn('status', ['present', 'late'])->count() ?? 0,
            'lateCount' => $employee?->attendances()->whereBetween('tanggal', [$monthStart, $today])->where('status', 'late')->count() ?? 0,
            'leaveCount' => $employee?->leaveRequests()->whereMonth('tanggal_mulai', $today->month)->where('status', 'approved')->count() ?? 0,
            'visitCount' => auth()->user()->isAdmin()
                ? VisitAttendance::whereDate('tanggal', $today->toDateString())->count()
                : ($employee?->visitAttendances()->whereDate('tanggal', $today->toDateString())->count() ?? 0),
            'adminStats' => auth()->user()->isAdmin() ? array_merge([
                'employees' => Employee::where('aktif', true)->count(),
                'present' => Attendance::whereDate('tanggal', $today)->whereIn('status', ['present', 'late'])->count(),
                'late' => Attendance::whereDate('tanggal', $today)->where('status', 'late')->count(),
                'pendingLeaves' => LeaveRequest::where('status', 'pending')->count(),
            ], $this->adminInsights($today, $monthStart)) : null,
        ]);
    }

    /**
     * Data dasbor admin: grafik 6 bulan terakhir, top telat bulan berjalan,
     * dan antrean persetujuan cuti. Terpisah agar index() tetap ringkas.
     */
    private function adminInsights(Carbon $today, Carbon $monthStart): array
    {
        $chartStart = $monthStart->copy()->subMonths(5);
        $rows = Attendance::selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as ym, status, COUNT(*) as total")
            ->where('tanggal', '>=', $chartStart->toDateString())
            ->whereIn('status', ['present', 'late'])
            ->groupBy('ym', 'status')
            ->get();

        $labels = $present = $late = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->startOfMonth()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');
            $present[] = (int) $rows->where('ym', $month->format('Y-m'))->where('status', 'present')->sum('total');
            $late[] = (int) $rows->where('ym', $month->format('Y-m'))->where('status', 'late')->sum('total');
        }

        return [
            'chart' => ['labels' => $labels, 'present' => $present, 'late' => $late],
            'topLate' => Attendance::with('employee')
                ->whereBetween('tanggal', [$monthStart->toDateString(), $today->toDateString()])
                ->where('status', 'late')
                ->selectRaw('karyawan_id, COUNT(*) as late_count, COALESCE(SUM(menit_telat), 0) as late_minutes')
                ->groupBy('karyawan_id')
                ->orderByDesc('late_count')
                ->limit(5)
                ->get(),
            'pendingApprovals' => LeaveRequest::with('employee')
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
            'notCheckedIn' => Employee::with('user')->where('aktif', true)
                ->whereDoesntHave('attendances', function ($query) use ($today) {
                    $query->whereDate('tanggal', $today)->whereNotNull('waktu_masuk');
                })
                ->orderBy('nama')
                ->get(['id', 'kode_karyawan', 'nama', 'telepon']),
        ];
    }
}
