<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $this->period($request->input('period'));
        $employeeId = $request->user()->isAdmin() ? $request->integer('karyawan_id') : $request->user()->employee?->id;
        $payrolls = Payroll::with('employee.user', 'employee.position')
            ->whereDate('periode_mulai', $period['start'])
            ->whereDate('periode_selesai', $period['end'])
            ->when($employeeId, fn ($query) => $query->where('karyawan_id', $employeeId))
            ->latest()
            ->paginate(15);

        return view('payroll.index', [
            'payrolls' => $payrolls,
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('aktif', true)->orderBy('kode_karyawan')->get() : collect(),
            'period' => $period,
            'selectedEmployee' => $employeeId,
        ]);
    }

    public function generate(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'karyawan_id' => ['nullable', 'exists:karyawan,id'],
            'force' => ['nullable', 'boolean'],
        ]);
        $period = $this->period($data['period']);
        $force = (bool) ($data['force'] ?? false);
        $employees = Employee::with('attendances', 'rewards', 'punishments', 'salaryComponents')->where('aktif', true)->when($data['karyawan_id'] ?? null, fn ($query, $id) => $query->whereKey($id))->get();
        $skipped = 0;

        DB::transaction(function () use ($employees, $period, $force, &$skipped) {
            foreach ($employees as $employee) {
                // Payroll yang sudah diverifikasi/dibayar terkunci agar tidak tertimpa
                // generate ulang secara diam-diam — kecuali admin mencentang Timpa.
                $existing = Payroll::where('karyawan_id', $employee->id)
                    ->whereDate('periode_mulai', $period['start'])
                    ->whereDate('periode_selesai', $period['end'])
                    ->first();
                if ($existing && $existing->status !== 'draft' && ! $force) {
                    $skipped++;

                    continue;
                }
                $attendance = $employee->attendances->filter(fn ($item) => $item->tanggal->betweenIncluded($period['start'], $period['end']));
                $rewards = $employee->rewards->filter(fn ($item) => $item->diberikan_pada->betweenIncluded($period['start'], $period['end']));
                $punishments = $employee->punishments->filter(fn ($item) => $item->dijatuhkan_pada->betweenIncluded($period['start'], $period['end']));
                $activeRates = $employee->salaryComponents->where('aktif', true);
                $lateRate = (float) ($activeRates->firstWhere('jenis', 'late_deduction')?->nominal ?? 1000);
                $absenceRate = (float) ($activeRates->firstWhere('jenis', 'absence_deduction')?->nominal ?? 0);
                $lateDeduction = $attendance->sum('menit_telat') * $lateRate;
                $absenceDays = max(0, $this->workdays($period['start'], $period['end']) - $attendance->whereIn('status', ['present', 'late', 'leave', 'permission', 'sick', 'duty'])->count());
                $absenceDeduction = $absenceDays * $absenceRate;
                $rewardTotal = $rewards->sum('nominal');
                $punishmentTotal = $punishments->sum('nominal');
                $basicSalary = (float) $employee->gaji_pokok;
                $allowance = (float) $employee->tunjangan_kehadiran;
                $netSalary = $basicSalary + $allowance + $rewardTotal - $lateDeduction - $absenceDeduction - $punishmentTotal;

                // Kolom period_* bertipe DATE: kunci lookup harus tanggal saja (tanpa jam),
                // kalau tidak updateOrCreate tidak pernah cocok dan selalu menabrak unique.
                Payroll::updateOrCreate(['karyawan_id' => $employee->id, 'periode_mulai' => $period['start']->toDateString(), 'periode_selesai' => $period['end']->toDateString()], [
                    'gaji_pokok' => $basicSalary, 'tunjangan_kehadiran' => $allowance, 'total_reward' => $rewardTotal,
                    'potongan_telat' => $lateDeduction, 'potongan_absen' => $absenceDeduction, 'total_hukuman' => $punishmentTotal,
                    'gaji_bersih' => max(0, $netSalary), 'status' => 'draft',
                ]);
            }
        });

        ActivityLogger::log('payroll.generate', null, [
            'periode' => $data['period'],
            'karyawan_id' => $data['karyawan_id'] ?? 'semua',
            'timpa_terkunci' => $force,
            'dilewati_terkunci' => $skipped,
        ]);

        $message = 'Penggajian periode '.$data['period'].' berhasil dibuat.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' data dilewati karena sudah diverifikasi/dibayar — centang "Timpa" bila memang ingin menghitung ulang.';
        }

        return back()->with('success', $message);
    }

    public function updateStatus(Request $request, Payroll $payroll)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,verified,paid']]);
        // Uang sudah keluar tidak boleh diam-diam kembali jadi draft;
        // lewat verified dulu agar jejak audit jelas.
        if ($payroll->status === 'paid' && $data['status'] === 'draft') {
            return back()->with('error', 'Payroll yang sudah dibayar tidak dapat langsung dikembalikan ke draft. Ubah ke Verified terlebih dahulu bila ada koreksi.');
        }
        $from = $payroll->status;
        $payroll->update($data);

        ActivityLogger::log('payroll.status', $payroll, [
            'dari' => $from,
            'ke' => $data['status'],
            'karyawan_id' => $payroll->karyawan_id,
        ]);

        return back()->with('success', 'Status payroll berhasil diperbarui.');
    }

    /**
     * Slip gaji per karyawan. Karyawan hanya boleh melihat slipnya sendiri.
     */
    public function show(Request $request, Payroll $payroll)
    {
        $this->authorizeSlip($request, $payroll);

        return view('payroll.show', $this->slipData($payroll) + ['title' => 'Slip Gaji']);
    }

    public function exportPdf(Request $request, Payroll $payroll)
    {
        $this->authorizeSlip($request, $payroll);
        $payroll->loadMissing('employee.user', 'employee.position');

        $pdf = Pdf::loadView('payroll.slip-pdf', $this->slipData($payroll))->setPaper('a4', 'portrait');

        return $pdf->download('slip-gaji-'.($payroll->employee?->kode_karyawan ?? $payroll->karyawan_id).'-'.$payroll->periode_mulai->format('Y-m').'.pdf');
    }

    private function authorizeSlip(Request $request, Payroll $payroll): void
    {
        abort_unless(
            $request->user()->isAdmin() || $payroll->karyawan_id === $request->user()->employee?->id,
            403,
            'Anda tidak berhak melihat slip gaji ini.'
        );
    }

    /**
     * Rincian slip: ringkasan kehadiran + item reward/punishment periode berjalan
     * + tarif yang dipakai (diturunkan dari nilai tersimpan).
     */
    private function slipData(Payroll $payroll): array
    {
        $payroll->loadMissing('employee.user', 'employee.position', 'employee.salaryComponents');
        $start = $payroll->periode_mulai->copy()->startOfDay();
        $end = $payroll->periode_selesai->copy()->endOfDay();

        $attendances = $payroll->employee
            ? $payroll->employee->attendances()->whereBetween('tanggal', [$start, $end])->orderBy('tanggal')->get()
            : collect();
        $lateMinutes = (int) $attendances->sum('menit_telat');
        $counted = $attendances->whereIn('status', ['present', 'late', 'leave', 'permission', 'sick', 'duty'])->count();
        $absenceDays = max(0, $this->workdays($start->copy()->startOfDay(), $end->copy()->startOfDay()) - $counted);

        $rates = $payroll->employee?->salaryComponents->where('aktif', true) ?? collect();

        return [
            'payroll' => $payroll,
            'periodLabel' => $payroll->periode_mulai->translatedFormat('F Y'),
            'presentDays' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'lateDays' => $attendances->where('status', 'late')->count(),
            'lateMinutes' => $lateMinutes,
            'absenceDays' => $absenceDays,
            'workdays' => $this->workdays($start->copy()->startOfDay(), $end->copy()->startOfDay()),
            'lateRate' => $lateMinutes > 0 ? (float) $payroll->potongan_telat / $lateMinutes : (float) ($rates->firstWhere('jenis', 'late_deduction')?->nominal ?? 1000),
            'absenceRate' => $absenceDays > 0 ? (float) $payroll->potongan_absen / $absenceDays : (float) ($rates->firstWhere('jenis', 'absence_deduction')?->nominal ?? 0),
            'rewards' => $payroll->employee
                ? $payroll->employee->rewards()->whereBetween('diberikan_pada', [$start, $end])->orderBy('diberikan_pada')->get()
                : collect(),
            'punishments' => $payroll->employee
                ? $payroll->employee->punishments()->whereBetween('dijatuhkan_pada', [$start, $end])->orderBy('dijatuhkan_pada')->get()
                : collect(),
        ];
    }

    private function period(?string $value): array
    {
        $date = Carbon::createFromFormat('Y-m', $value ?: now()->format('Y-m'));

        return ['start' => $date->copy()->startOfMonth(), 'end' => $date->copy()->endOfMonth(), 'value' => $date->format('Y-m')];
    }

    /**
     * Hari kerja = Senin-Jumat di luar tanggal hari libur.
     * Sama seperti ReportController agar potongan absen konsisten dengan laporan.
     */
    private function workdays(Carbon $start, Carbon $end): int
    {
        $holidayDates = Holiday::whereBetween('tanggal', [$start, $end])->pluck('tanggal')->map(fn ($date) => Carbon::parse($date)->toDateString())->all();
        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday() && ! in_array($date->toDateString(), $holidayDates, true)) {
                $days++;
            }
        }

        return $days;
    }
}
