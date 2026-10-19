<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Punishment;
use App\Models\Reward;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecognitionController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->user()->isAdmin() ? $request->integer('karyawan_id') : $request->user()->employee?->id;
        $employees = $request->user()->isAdmin() ? Employee::with('user')->where('aktif', true)->orderBy('kode_karyawan')->get() : collect();

        return view('recognition.index', [
            'rewards' => Reward::with('employee.user')->when($employeeId, fn ($query) => $query->where('karyawan_id', $employeeId))->latest('diberikan_pada')->paginate(10, ['*'], 'rewards_page'),
            'punishments' => Punishment::with('employee.user')->when($employeeId, fn ($query) => $query->where('karyawan_id', $employeeId))->latest('dijatuhkan_pada')->paginate(10, ['*'], 'punishments_page'),
            'employees' => $employees,
            'selectedEmployee' => $employeeId,
            'pointSettings' => AttendanceSetting::current(),
        ]);
    }

    /**
     * Generate poin punishment otomatis per bulan dari total menit terlambat.
     * Idempoten: baris otomatis periode yang sama dihapus dulu lalu dibuat ulang.
     * Input manual tidak tersentuh dan potongan gaji tidak berubah (nominal = 0).
     */
    public function generateAutoPoints(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $settings = AttendanceSetting::current();
        if (! $settings->poin_otomatis_aktif) {
            return back()->with('error', 'Generate poin otomatis sedang nonaktif. Aktifkan dulu di Pengaturan Absensi.');
        }
        $block = (int) $settings->blok_menit_poin;
        $perBlock = (int) $settings->poin_per_blok;
        if ($block < 1) {
            return back()->with('error', 'Blok menit aturan poin tidak valid. Periksa Pengaturan Absensi.');
        }

        $start = Carbon::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $label = $start->translatedFormat('F Y');
        $generated = 0;

        DB::transaction(function () use ($start, $end, $label, $block, $perBlock, &$generated) {
            Punishment::where('otomatis', true)->whereBetween('dijatuhkan_pada', [$start, $end])->delete();

            foreach (Employee::where('aktif', true)->get() as $employee) {
                $lateDays = $employee->attendances()
                    ->whereBetween('tanggal', [$start, $end])
                    ->where('status', 'late')
                    ->get();
                $totalLate = (int) $lateDays->sum('menit_telat');
                if ($totalLate <= 0) {
                    continue;
                }
                $points = (int) ceil($totalLate / $block) * $perBlock;
                if ($points <= 0) {
                    continue;
                }
                Punishment::create([
                    'karyawan_id' => $employee->id,
                    'jenis' => 'points_deduction',
                    'nominal' => 0,
                    'poin' => $points,
                    'otomatis' => true,
                    'judul' => 'Poin keterlambatan '.$label,
                    'deskripsi' => 'Otomatis: total '.$totalLate.' menit terlambat dari '.$lateDays->count().' hari kehadiran pada '.$label.' ('.$block.' menit = '.$perBlock.' poin).',
                    'dijatuhkan_pada' => $end->toDateString(),
                ]);
                $generated++;
            }
        });

        ActivityLogger::log('punishment.auto-points', null, ['periode' => $label, 'karyawan' => $generated]);

        return back()->with('success', 'Poin otomatis periode '.$label.' digenerate untuk '.$generated.' karyawan.');
    }

    public function storeReward(Request $request)
    {
        $data = $request->validate([
            'karyawan_id' => ['required', 'exists:karyawan,id'],
            'jenis' => ['required', 'in:bonus,points'],
            'nominal' => ['nullable', 'numeric', 'min:0'],
            'poin' => ['nullable', 'integer', 'min:0'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'diberikan_pada' => ['required', 'date'],
        ]);
        // Kolom nominal/poin NOT NULL: nol-kan field yang tidak relevan dengan tipe.
        if ($data['jenis'] === 'points') {
            $data['nominal'] = 0;
            $data['poin'] ??= 0;
        } else {
            $data['poin'] = 0;
            $data['nominal'] ??= 0;
        }
        $reward = Reward::create($data);

        ActivityLogger::log('reward.create', $reward, [
            'karyawan_id' => $reward->karyawan_id,
            'jenis' => $reward->jenis,
            'nominal' => $reward->nominal,
        ]);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function storePunishment(Request $request)
    {
        $data = $request->validate([
            'karyawan_id' => ['required', 'exists:karyawan,id'],
            'jenis' => ['required', 'in:warning,points_deduction,salary_deduction,administrative'],
            'nominal' => ['nullable', 'numeric', 'min:0'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'dijatuhkan_pada' => ['required', 'date'],
        ]);
        // Kolom nominal NOT NULL: punishment tanpa nominal (mis. peringatan) disimpan 0.
        $data['nominal'] ??= 0;
        $punishment = Punishment::create($data);

        ActivityLogger::log('punishment.create', $punishment, [
            'karyawan_id' => $punishment->karyawan_id,
            'jenis' => $punishment->jenis,
            'nominal' => $punishment->nominal,
        ]);

        return back()->with('success', 'Punishment berhasil ditambahkan.');
    }

    public function destroyReward(Request $request, Reward $reward)
    {
        abort_unless($request->user()->isAdmin(), 403);
        ActivityLogger::log('reward.delete', $reward, ['karyawan_id' => $reward->karyawan_id, 'judul' => $reward->judul]);
        $reward->delete();

        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function destroyPunishment(Request $request, Punishment $punishment)
    {
        abort_unless($request->user()->isAdmin(), 403);
        ActivityLogger::log('punishment.delete', $punishment, ['karyawan_id' => $punishment->karyawan_id, 'judul' => $punishment->judul]);
        $punishment->delete();

        return back()->with('success', 'Punishment berhasil dihapus.');
    }
}
