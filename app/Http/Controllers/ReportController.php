<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Exports\ReportSheet;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Punishment;
use App\Models\Reward;
use App\Models\VisitAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->reportData($filters);

        return view('reports.index', $data + ['filters' => $filters]);
    }

    public function attendance(Request $request)
    {
        return $this->index($request->merge(['type' => 'attendance']));
    }

    public function leave(Request $request)
    {
        return $this->index($request->merge(['type' => 'leaves']));
    }

    public function payroll(Request $request)
    {
        return $this->index($request->merge(['type' => 'payroll']));
    }

    public function rewardPunishment(Request $request)
    {
        return $this->index($request->merge(['type' => 'reward-punishment']));
    }

    public function employees(Request $request)
    {
        return $this->index($request->merge(['type' => 'employees']));
    }

    public function print(Request $request)
    {
        $filters = $this->filters($request);

        return view('reports.print', $this->reportData($filters) + ['filters' => $filters]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->filters($request);
        $pdf = Pdf::loadView('reports.print', $this->reportData($filters) + ['filters' => $filters])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-'.$filters['type'].'-'.$filters['period_slug'].'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->reportData($filters);

        return Excel::download(new ReportExport($data['exportSheets']), 'laporan-'.$filters['type'].'-'.$filters['period_slug'].'.xlsx');
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:all,attendance,leaves,payroll,reward-punishment,employees,visits'],
            'karyawan_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'period' => ['nullable', 'in:weekly,monthly,yearly,custom'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'week' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);
        // Karyawan hanya boleh melihat + mengunduh laporannya sendiri.
        if (! $request->user()->isAdmin()) {
            $employee = $request->user()->employee;
            abort_unless($employee?->id, 403, 'Akun karyawan belum terhubung.');
            $data['karyawan_id'] = $employee->id;
        }
        $data['type'] = $data['type'] ?? 'all';
        $data['period'] = $data['period'] ?? 'monthly';
        $data['month'] = (int) ($data['month'] ?? now()->month);
        $data['year'] = (int) ($data['year'] ?? now()->year);

        switch ($data['period']) {
            case 'weekly':
                $week = isset($data['week']) ? Carbon::parse($data['week']) : now();
                $data['start'] = $week->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $data['end'] = $week->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $data['week'] = $week->toDateString();
                break;
            case 'yearly':
                $data['start'] = Carbon::create($data['year'], 1, 1)->startOfDay();
                $data['end'] = Carbon::create($data['year'], 12, 31)->endOfDay();
                break;
            case 'custom':
                $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now()->startOfMonth();
                $end = isset($data['end_date']) ? Carbon::parse($data['end_date']) : now()->endOfMonth();
                // Batasi maksimal 366 hari agar cetak PDF/Excel tidak jebol memori.
                if ($start->diffInDays($end) > 366) {
                    throw ValidationException::withMessages([
                        'end_date' => 'Rentang kustom maksimal 366 hari. Gunakan mode tahunan untuk rentang lebih panjang.',
                    ]);
                }
                $data['start'] = $start->copy()->startOfDay();
                $data['end'] = $end->copy()->endOfDay();
                $data['start_date'] = $start->toDateString();
                $data['end_date'] = $end->toDateString();
                break;
            default: // monthly (format lama ?month=&year= tetap jalan)
                $data['period'] = 'monthly';
                $data['start'] = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();
                $data['end'] = $data['start']->copy()->endOfMonth();
                break;
        }

        ['label' => $data['period_label'], 'slug' => $data['period_slug']] = $this->periodLabel($data);

        return $data;
    }

    /**
     * Label periode Indonesia untuk kop laporan + slug aman untuk nama file.
     */
    private function periodLabel(array $data): array
    {
        $start = $data['start'];
        $end = $data['end'];

        switch ($data['period']) {
            case 'weekly':
                $label = $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y');
                $slug = 'minggu-'.$start->format('Y-m-d').'-sd-'.$end->format('Y-m-d');
                break;
            case 'yearly':
                $label = 'Tahun '.$start->format('Y');
                $slug = $start->format('Y');
                break;
            case 'custom':
                $label = $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y');
                $slug = $start->format('Y-m-d').'-sd-'.$end->format('Y-m-d');
                break;
            default:
                $label = $start->translatedFormat('F Y');
                $slug = $start->format('Y-m');
                break;
        }

        return ['label' => $label, 'slug' => $slug];
    }

    private function reportData(array $filters): array
    {
        $employeeQuery = Employee::withTrashed()->with('user', 'position')->when($filters['karyawan_id'] ?? null, fn ($query, $id) => $query->whereKey($id));
        if (in_array($filters['status'] ?? null, ['active', 'inactive'], true)) {
            $employeeQuery->where('aktif', $filters['status'] === 'active');
        }
        $employees = $employeeQuery->orderBy('kode_karyawan')->get();
        $employeeIds = $employees->pluck('id');
        $attendances = Attendance::with('employee.user', 'employee.position')->whereIn('karyawan_id', $employeeIds)->whereBetween('tanggal', [$filters['start'], $filters['end']])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->get();
        // Overlap penuh: cuti yang mengiris rentang ikut tampil (termasuk yang membentang
        // melewati awal/akhir periode, mis. laporan mingguan di tengah cuti panjang).
        $leaves = LeaveRequest::with('employee.user')->whereIn('karyawan_id', $employeeIds)->where(function ($query) use ($filters) {
            $query->whereDate('tanggal_mulai', '<=', $filters['end']->toDateString())->whereDate('tanggal_selesai', '>=', $filters['start']->toDateString());
        })->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest('tanggal_mulai')->get();
        // Payroll disimpan per bulan, jadi untuk periode non-bulanan ambil bulan yang beririsan.
        $payrolls = Payroll::with('employee.user')->whereIn('karyawan_id', $employeeIds)->whereDate('periode_mulai', '<=', $filters['end']->toDateString())->whereDate('periode_selesai', '>=', $filters['start']->toDateString())->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->get();
        $rewards = Reward::with('employee.user')->whereIn('karyawan_id', $employeeIds)->whereBetween('diberikan_pada', [$filters['start'], $filters['end']])->get();
        $punishments = Punishment::with('employee.user')->whereIn('karyawan_id', $employeeIds)->whereBetween('dijatuhkan_pada', [$filters['start'], $filters['end']])->get();
        $visits = VisitAttendance::with('employee.user')->whereIn('karyawan_id', $employeeIds)->whereBetween('tanggal', [$filters['start'], $filters['end']])->latest('tanggal')->latest('id')->get();
        $attendanceSummary = $this->attendanceSummary($employees, $attendances, $filters);
        $payrollSummary = ['total' => $payrolls->sum('gaji_pokok'), 'reward' => $payrolls->sum('total_reward'), 'deduction' => $payrolls->sum(fn ($item) => $item->potongan_telat + $item->potongan_absen + $item->total_hukuman), 'net' => $payrolls->sum('gaji_bersih')];
        $exportSheets = $this->exportSheets($filters, $attendanceSummary, $leaves, $payrolls, $rewards, $punishments, $employees, $visits);

        return compact('employees', 'attendances', 'leaves', 'payrolls', 'rewards', 'punishments', 'visits', 'attendanceSummary', 'payrollSummary', 'exportSheets');
    }

    private function attendanceSummary($employees, $attendances, array $filters)
    {
        $workdays = $this->workdays($filters['start'], $filters['end']);

        return $employees->map(function ($employee) use ($attendances, $workdays) {
            $items = $attendances->where('karyawan_id', $employee->id);
            $present = $items->whereIn('status', ['present', 'late'])->count();

            return ['employee' => $employee, 'present' => $present, 'late' => $items->where('status', 'late')->count(), 'permission' => $items->where('status', 'permission')->count(), 'leave' => $items->where('status', 'leave')->count(), 'duty' => $items->where('status', 'duty')->count(), 'total' => $workdays, 'percentage' => $workdays ? round($present / $workdays * 100, 1) : 0];
        });
    }

    private function workdays(Carbon $start, Carbon $end): int
    {
        $holidayDates = \App\Models\Holiday::whereBetween('tanggal', [$start, $end])->pluck('tanggal')->map(fn ($date) => Carbon::parse($date)->toDateString())->all();
        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday() && ! in_array($date->toDateString(), $holidayDates, true)) {
                $days++;
            }
        }

        return $days;
    }

    private function exportSheets(array $filters, $summary, $leaves, $payrolls, $rewards, $punishments, $employees, $visits): array
    {
        $type = $filters['type'];
        $period = $filters['period_label'] ?? $filters['start']->translatedFormat('F Y');
        $sheet = fn (string $title, string $report, array $headings, array $rows, array $formats = [], string $accent = '1E5AA8', ?array $footer = null, ?string $percentCol = null) => new ReportSheet($title, $report, $period, $headings, $rows, $formats, $accent, $footer, $percentCol);

        $sheets = [
            'attendance' => fn () => $sheet('Kehadiran', 'Laporan Kehadiran',
                ['No', 'ID Karyawan', 'Nama', 'Jabatan', 'Hadir', 'Terlambat', 'Izin', 'Cuti', 'Dinas', 'Total Hari', 'Persentase'],
                $summary->values()->map(fn ($item, $i) => [
                    $i + 1, $item['employee']->kode_karyawan, $item['employee']->display_name,
                    $item['employee']->position?->nama ?? '-', $item['present'], $item['late'],
                    $item['permission'], $item['leave'], $item['duty'], $item['total'],
                    $item['total'] ? round($item['present'] / $item['total'], 3) : 0,
                ])->all(),
                ['K' => '0.0%'], '1E5AA8', null, 'K'),
            'leaves' => fn () => $sheet('Cuti & Izin', 'Laporan Cuti & Izin',
                ['No', 'ID Karyawan', 'Nama', 'Jenis', 'Mulai', 'Selesai', 'Durasi', 'Status'],
                $leaves->values()->map(fn ($item, $i) => [
                    $i + 1, $item->employee?->kode_karyawan, $item->employee?->user?->name,
                    $item->jenis === 'sick' ? 'Sakit' : ($item->jenis === 'duty' ? 'Dinas Keluar' : 'Cuti'),
                    $item->tanggal_mulai->format('d/m/Y'), $item->tanggal_selesai->format('d/m/Y'),
                    $item->jumlah_hari.' hari', $this->leaveStatusLabel($item->status),
                ])->all(),
                [], '0E7C86'),
            'payroll' => fn () => $sheet('Penggajian', 'Laporan Penggajian',
                ['No', 'ID Karyawan', 'Nama', 'Periode', 'Gaji Pokok', 'Reward', 'Potongan', 'Gaji Bersih', 'Status'],
                $payrolls->values()->map(fn ($item, $i) => [
                    $i + 1, $item->employee?->kode_karyawan, $item->employee?->user?->name,
                    $item->periode_mulai->format('m/Y'), $item->gaji_pokok, $item->total_reward,
                    $item->potongan_telat + $item->potongan_absen + $item->total_hukuman,
                    $item->gaji_bersih, $this->payrollStatusLabel($item->status),
                ])->all(),
                ['E' => '"Rp" #,##0', 'F' => '"Rp" #,##0', 'G' => '"Rp" #,##0', 'H' => '"Rp" #,##0'],
                '15803D',
                ['', '', '', 'TOTAL', $payrolls->sum('gaji_pokok'), $payrolls->sum('total_reward'),
                    $payrolls->sum(fn ($item) => $item->potongan_telat + $item->potongan_absen + $item->total_hukuman),
                    $payrolls->sum('gaji_bersih'), '']),
            'reward-punishment' => fn () => $sheet('Reward & Punishment', 'Laporan Reward & Punishment',
                ['No', 'Nama', 'Jenis', 'Keterangan', 'Nilai', 'Tanggal'],
                $rewards->map(fn ($item) => [$item->employee?->user?->name, 'Reward', $item->judul, 'Rp '.number_format($item->nominal, 0, ',', '.'), $item->diberikan_pada->format('d/m/Y')])
                    ->concat($punishments->map(fn ($item) => [$item->employee?->user?->name, 'Punishment', $item->judul, $item->jenis === 'points_deduction' && $item->poin > 0 ? $item->poin.' poin' : 'Rp '.number_format($item->nominal, 0, ',', '.'), $item->dijatuhkan_pada->format('d/m/Y')]))
                    ->values()->map(fn ($row, $i) => [$i + 1, ...$row])->all(),
                [], 'B45309'),
            'employees' => fn () => $sheet('Data Karyawan', 'Laporan Data Karyawan',
                ['No', 'ID Karyawan', 'Nama', 'Jabatan', 'Tanggal Bergabung', 'Status'],
                $employees->values()->map(fn ($item, $i) => [
                    $i + 1, $item->kode_karyawan, $item->display_name, $item->position?->nama ?? '-',
                    $item->tanggal_masuk?->format('d/m/Y') ?? '-', $item->aktif ? 'Aktif' : 'Nonaktif',
                ])->all(),
                [], '475569'),
            'visits' => fn () => $sheet('Kunjungan', 'Laporan Kunjungan',
                ['No', 'ID Karyawan', 'Nama', 'Judul Kunjungan', 'Deskripsi', 'Tanggal', 'Koordinat'],
                $visits->values()->map(fn ($item, $i) => [
                    $i + 1, $item->employee?->kode_karyawan, $item->employee?->user?->name,
                    $item->judul, $item->deskripsi ?: '-', $item->tanggal->format('d/m/Y'),
                    ! empty($filters['karyawan_id']) && $item->lintang && $item->bujur
                        ? number_format($item->lintang, 5).', '.number_format($item->bujur, 5) : '-',
                ])->all(),
                [], '7C3AED'),
        ];

        if ($type === 'all') {
            return array_map(fn ($build) => $build(), array_values($sheets));
        }

        return isset($sheets[$type]) ? [$sheets[$type]()] : [$sheets['attendance']()];
    }

    private function leaveStatusLabel(?string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    }

    private function payrollStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Dibayar',
            'verified' => 'Diverifikasi',
            default => 'Draft',
        };
    }
}
