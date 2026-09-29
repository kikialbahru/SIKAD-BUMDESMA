<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\VisitAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'karyawan_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $monthValue = $filters['month'] ?? now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $monthValue)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $monthValue)->endOfMonth();

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

        $query = VisitAttendance::with('employee.user', 'employee.position')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->latest('tanggal')
            ->latest('id');

        $employee = $request->user()->employee;
        if (! $request->user()->isAdmin()) {
            $query->where('karyawan_id', $employee?->id ?? 0);
        } elseif (! empty($filters['karyawan_id'])) {
            $query->where('karyawan_id', $filters['karyawan_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('judul', 'like', '%'.$filters['search'].'%');
        }

        $visits = $query->paginate(15)->withQueryString();

        // Lokasi bersifat per karyawan: admin hanya melihatnya saat filter satu karyawan dipilih.
        $showLocation = ! $request->user()->isAdmin() || ! empty($filters['karyawan_id']);

        // Titik peta sebaran: seluruh hasil filter yang berkoordinat (maks 200 titik).
        $mapPoints = $showLocation ? (clone $query)->whereNotNull('lintang')->whereNotNull('bujur')
            ->reorder()->latest('tanggal')->latest('id')->limit(200)->get()
            ->map(fn (VisitAttendance $item) => [
                'lat' => (float) $item->lintang,
                'lng' => (float) $item->bujur,
                'title' => $item->judul,
                'employee' => $item->employee?->user?->name ?? '-',
                'date' => $item->tanggal->translatedFormat('d M Y'),
                'url' => route('visits.show', $item),
            ])->values() : collect();

        // Rekap: hitungan kunjungan hari ini + total bulan berjalan.
        $todayCount = 0;
        $monthCount = 0;
        if ($employee) {
            $todayCount = VisitAttendance::where('karyawan_id', $employee->id)
                ->whereDate('tanggal', today())
                ->count();
            $monthCount = VisitAttendance::where('karyawan_id', $employee->id)
                ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
                ->count();
        }
        $summary = collect();
        if ($request->user()->isAdmin()) {
            $summary = Employee::with('user')
                ->where('aktif', true)
                ->orderBy('kode_karyawan')
                ->get()
                ->map(function (Employee $item) use ($start, $end) {
                    return [
                        'employee' => $item,
                        'today' => VisitAttendance::where('karyawan_id', $item->id)
                            ->whereDate('tanggal', today())
                            ->count(),
                        'month' => VisitAttendance::where('karyawan_id', $item->id)
                            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
                            ->count(),
                    ];
                });
        }

        return view('visits.index', [
            'title' => 'Absen Kunjungan',
            'visits' => $visits,
            'filters' => $filters + ['month' => $monthValue],
            'monthNav' => $monthNav,
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'maxPerDay' => VisitAttendance::MAX_PER_DAY,
            'showLocation' => $showLocation,
            'mapPoints' => $mapPoints,
            'summary' => $summary,
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('aktif', true)->orderBy('kode_karyawan')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->aktif, 403, 'Akun karyawan belum terhubung.');

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'lintang' => ['required', 'numeric', 'between:-90,90'],
            'bujur' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $visitDay = Carbon::parse($data['tanggal']);
        $count = VisitAttendance::where('karyawan_id', $employee->id)
            ->whereDate('tanggal', $visitDay->toDateString())
            ->count();

        if ($count >= VisitAttendance::MAX_PER_DAY) {
            return back()
                ->withInput()
                ->with('error', 'Batas maksimal '.VisitAttendance::MAX_PER_DAY.' kunjungan per hari sudah tercapai ('.$visitDay->translatedFormat('d F Y').').');
        }

        $photo = $request->file('foto')->store('visit-attendances/'.now()->format('Y/m'), 'public');

        VisitAttendance::create([
            'karyawan_id' => $employee->id,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'tanggal' => $data['tanggal'],
            'foto' => $photo,
            'lintang' => $data['lintang'],
            'bujur' => $data['bujur'],
            'akurasi' => isset($data['akurasi']) ? (int) round($data['akurasi']) : null,
        ]);

        return back()->with('success', 'Absen kunjungan berhasil dicatat.');
    }

    public function show(Request $request, VisitAttendance $visit)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($visit->karyawan_id !== $request->user()->employee?->id, 403, 'Anda tidak berhak melihat kunjungan ini.');
        }

        return view('visits.show', [
            'title' => 'Detail Kunjungan',
            'visit' => $visit->load('employee.user', 'employee.position'),
        ]);
    }

    /**
     * Ubah kunjungan. Koordinat GPS tidak bisa diubah (bukti lokasi).
     * Pindah tanggal tetap menghormati batas harian dan tidak boleh ke masa depan.
     */
    public function update(Request $request, VisitAttendance $visit)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($visit->karyawan_id !== $request->user()->employee?->id, 403, 'Anda tidak berhak mengubah kunjungan ini.');
        }

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            // Locale aplikasi 'en' (tanpa lang/id) sehingga pesan bawaan berbahasa Inggris;
            // aturan tanggal kunci ini diberi pesan Indonesia eksplisit.
            'tanggal.before_or_equal' => 'Tanggal kunjungan tidak boleh melebihi hari ini.',
        ]);

        $newDay = Carbon::parse($data['tanggal'])->toDateString();
        if ($newDay !== $visit->tanggal->toDateString()) {
            $count = VisitAttendance::where('karyawan_id', $visit->karyawan_id)
                ->whereDate('tanggal', $newDay)
                ->count();
            if ($count >= VisitAttendance::MAX_PER_DAY) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tanggal tersebut sudah mencapai batas '.VisitAttendance::MAX_PER_DAY.' kunjungan per hari.',
                ]);
            }
        }

        $photo = $visit->foto;
        if ($request->hasFile('foto')) {
            Storage::disk('public')->delete($visit->foto);
            $photo = $request->file('foto')->store('visit-attendances/'.now()->format('Y/m'), 'public');
        }

        $visit->update([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'tanggal' => $data['tanggal'],
            'foto' => $photo,
        ]);

        return redirect()->route('visits.show', $visit)->with('success', 'Data kunjungan berhasil diubah.');
    }

    public function destroy(Request $request, VisitAttendance $visit)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($visit->karyawan_id !== $request->user()->employee?->id, 403, 'Anda tidak berhak menghapus kunjungan ini.');
        }

        Storage::disk('public')->delete($visit->foto);
        $visit->delete();

        return back()->with('success', 'Data kunjungan berhasil dihapus.');
    }
}
