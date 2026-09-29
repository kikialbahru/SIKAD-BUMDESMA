@extends('layouts.app')

@section('content')
    @php($type = $filters['type'])
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Laporan</h1>
            <p class="text-secondary mb-0">Rekapitulasi dan laporan Sistem Informasi BUMDESMA LKD TARUB</p>
            <span class="badge text-bg-light border mt-2">
                <i class="bi bi-calendar-range me-1"></i>Periode: {{ $filters['period_label'] ?? $filters['start']->translatedFormat('F Y') }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" target="_blank"
                href="{{ route('reports.print', request()->query()) }}">
                <i class="bi bi-printer me-2">
                </i>Cetak</a>
                <a
                class="btn btn-outline-danger" href="{{ route('reports.pdf', request()->query()) }}">
                <i
                    class="bi bi-file-earmark-pdf me-2">
                    </i>PDF</a>
                    <a class="btn btn-outline-success"
                href="{{ route('reports.excel', request()->query()) }}">
                <i
                    class="bi bi-file-earmark-excel me-2">
                    </i>Excel</a>
                    </div>
    </div>
    <div class="row g-3 mb-4">
        @foreach ([['bi-people', auth()->user()->isAdmin() ? 'Total Karyawan Aktif' : 'Karyawan', $employees->where('aktif', true)->count()], ['bi-calendar-check', 'Total Kehadiran', $attendances->whereIn('status', ['present', 'late'])->count()], ['bi-geo-alt', 'Total Kunjungan', $visits->count()], ['bi-calendar-heart', 'Pengajuan Cuti & Izin', $leaves->count()], ['bi-trophy', 'Reward & Punishment', $rewards->count() + $punishments->count()]] as [$icon, $label, $value])
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <i class="icon bi {{ $icon }}">
                        </i>
                        <small class="d-block text-secondary mt-2">{{ $label }}</small>
                        <strong class="fs-3">{{ $value }}</strong>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <i class="icon bi bi-wallet2">
                    </i>
                    <small class="d-block text-secondary mt-2">Total
                        Gaji Bersih</small>
                        <strong class="fs-5">Rp
                        {{ number_format($payrollSummary['net'], 0, ',', '.') }}
                        </strong>
                        </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-12 col-md-3">
                    <label class="form-label small">Jenis laporan</label>
                    <select
                        class="form-select" name="type">
                        <option value="all" @selected($type === 'all')>Semua laporan</option>
                        <option value="attendance" @selected($type === 'attendance')>Laporan kehadiran</option>
                        <option value="leaves" @selected($type === 'leaves')>Laporan cuti & izin</option>
                        <option value="payroll" @selected($type === 'payroll')>Laporan penggajian</option>
                        <option
                            value="reward-punishment"
                            @selected($type === 'reward-punishment')>Reward
                            &
                            punishment</option>
                        <option value="employees" @selected($type === 'employees')>Data karyawan</option>
                        <option value="visits" @selected($type === 'visits')>Kunjungan</option>
                    </select>
                    </div>
                @if(auth()->user()->isAdmin())
                <div class="col-6 col-md-2">
                    <label class="form-label small">Karyawan</label>
                    <select class="form-select"
                        name="karyawan_id">
                        <option value="">Semua karyawan</option>
                        @foreach ($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                @selected(($filters['karyawan_id'] ?? '') == $employee->id)>
                            {{ $employee->kode_karyawan }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" name="karyawan_id" value="{{ $filters['karyawan_id'] ?? '' }}">
                <div class="col-6 col-md-2">
                    <label class="form-label small">Karyawan</label>
                    <input class="form-control" value="Data saya" disabled>
                </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label small">Periode</label>
                    <select class="form-select"
                        name="period" id="filter-period">
                        <option value="weekly" @selected(($filters['period'] ?? 'monthly') === 'weekly')>Mingguan</option>
                        <option value="monthly" @selected(($filters['period'] ?? 'monthly') === 'monthly')>Bulanan</option>
                        <option value="yearly" @selected(($filters['period'] ?? 'monthly') === 'yearly')>Tahunan</option>
                        <option value="custom" @selected(($filters['period'] ?? 'monthly') === 'custom')>Kustom</option>
                    </select>
                </div>
                <div class="col-6 col-md-2" data-period-show="monthly">
                    <label class="form-label small">Bulan</label>
                    <select class="form-select"
                        name="month">
                        @foreach (range(1, 12) as $month)
                            <option value="{{ $month }}" @selected($filters['month'] === $month)>
                                {{ Carbon\Carbon::create()->month($month)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2" data-period-show="monthly yearly">
                    <label class="form-label small">Tahun</label>
                    <select class="form-select"
                        name="year">
                        @foreach (range(now()->year - 2, now()->year + 1) as $year)
                            <option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2" data-period-show="weekly">
                    <label class="form-label small">Minggu</label>
                    <input type="date" class="form-control" name="week"
                        value="{{ $filters['week'] ?? now()->toDateString() }}"
                        title="Pilih tanggal — diambil minggu (Senin–Minggu) yang memuatnya">
                </div>
                <div class="col-6 col-md-2" data-period-show="custom">
                    <label class="form-label small">Dari tanggal</label>
                    <input type="date" class="form-control" name="start_date"
                        value="{{ $filters['start_date'] ?? $filters['start']->toDateString() }}">
                </div>
                <div class="col-6 col-md-2" data-period-show="custom">
                    <label class="form-label small">Sampai tanggal</label>
                    <input type="date" class="form-control" name="end_date"
                        value="{{ $filters['end_date'] ?? $filters['end']->toDateString() }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select"
                        name="status">
                        <option value="">Semua status</option>
                        @foreach (['present' => 'Hadir', 'late' => 'Terlambat', 'permission' => 'Izin', 'leave' => 'Cuti', 'sick' => 'Sakit', 'absent' => 'Alpa', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'draft' => 'Draft', 'verified' => 'Diverifikasi', 'paid' => 'Dibayar'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i
                            class="bi bi-search">
                            </i>
                            </button>
                            <a class="btn btn-light border"
                        href="{{ route('reports.index') }}">
                        <i class="bi bi-arrow-counterclockwise">
                        </i>
                        </a>
                        </div>
            </form>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap mb-3 report-tabs">
        @foreach (['attendance' => 'Kehadiran', 'leaves' => 'Cuti & Izin', 'payroll' => 'Penggajian', 'reward-punishment' => 'Reward & Punishment', 'employees' => 'Data Karyawan', 'visits' => 'Kunjungan'] as $tab => $label)
            <a class="btn btn-sm {{ $type === $tab ? 'btn-primary' : 'btn-light border' }}"
                href="{{ request()->fullUrlWithQuery(['type' => $tab]) }}">{{ $label }}</a>
        @endforeach
    </div>
    @if ($type === 'attendance' || $type === 'all')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Kehadiran</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Karyawan</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Hadir</th>
                            <th>Terlambat</th>
                            <th>Izin</th>
                            <th>Cuti</th>
                            <th>Total Hari</th>
                            <th>Persentase</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceSummary as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['employee']->kode_karyawan }}</td>
                                <td>{{ $item['employee']->display_name }}</td>
                                <td>{{ $item['employee']->position?->nama ?? '-' }}</td>
                                <td>{{ $item['present'] }}</td>
                                <td>{{ $item['late'] }}</td>
                                <td>{{ $item['permission'] }}</td>
                                <td>{{ $item['leave'] }}</td>
                                <td>{{ $item['total'] }}</td>
                                <td>
                                    <span class="badge text-bg-primary">
                                        {{ $item['percentage'] }}%</span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ request()->fullUrlWithQuery(['type' => 'attendance', 'karyawan_id' => $item['employee']->id]) }}"
                                        title="Detail">
                                        <i class="bi bi-eye">
                                        </i>
                                        </a>
                                        </td>
                        </tr>@empty<tr>
                                <td colspan="11" class="text-center text-secondary py-5">Tidak ada data laporan pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if ($type === 'leaves' || $type === 'all')
        <div class="report-card card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Cuti & Izin</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Karyawan</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Durasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->employee?->kode_karyawan }}</td>
                                <td>{{ $item->employee?->user?->name }}</td>
                                <td>{{ ucfirst($item->jenis) }}</td>
                                <td>{{ $item->tanggal_mulai->format('d/m/Y') }}</td>
                                <td>{{ $item->tanggal_selesai->format('d/m/Y') }}</td>
                                <td>{{ $item->jumlah_hari }} hari</td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($item->status) }}
                                        </span>
                                </td>
                        </tr>@empty<tr>
                                <td colspan="8" class="text-center text-secondary py-5">Tidak ada data laporan pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if ($type === 'payroll' || $type === 'all')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <small>Total Gaji Pokok</small>
                    <strong>Rp
                        {{ number_format($payrolls->sum('gaji_pokok'), 0, ',', '.') }}
                        </strong>
                        </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <small>Total Reward</small>
                    <strong class="text-success">Rp
                        {{ number_format($payrollSummary['reward'], 0, ',', '.') }}
                        </strong>
                        </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <small>Total Potongan</small>
                    <strong class="text-danger">Rp
                        {{ number_format($payrollSummary['deduction'], 0, ',', '.') }}
                        </strong>
                        </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <small>Gaji Bersih</small>
                    <strong class="text-primary">Rp
                        {{ number_format($payrollSummary['net'], 0, ',', '.') }}
                        </strong>
                        </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Penggajian</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Karyawan</th>
                            <th>Nama</th>
                            <th>Periode</th>
                            <th>Gaji Pokok</th>
                            <th>Reward</th>
                            <th>Potongan</th>
                            <th>Gaji Bersih</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->employee?->kode_karyawan }}</td>
                                <td>{{ $item->employee?->user?->name }}</td>
                                <td>{{ $item->periode_mulai->format('m/Y') }}</td>
                                <td>Rp {{ number_format($item->gaji_pokok, 0, ',', '.') }}</td>
                                <td class="text-success">Rp {{ number_format($item->total_reward, 0, ',', '.') }}</td>
                                <td class="text-danger">Rp
                                    {{ number_format($item->potongan_telat + $item->potongan_absen + $item->total_hukuman, 0, ',', '.') }}
                                </td>
                                <td>
                                    <strong>Rp {{ number_format($item->gaji_bersih, 0, ',', '.') }}
                                    </strong>
                                </td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $item->status === 'paid' ? 'success' : ($item->status === 'verified' ? 'primary' : 'warning') }}">
                                        {{ ucfirst($item->status) }}
                                        </span>
                                </td>
                        </tr>@empty<tr>
                                <td colspan="9" class="text-center text-secondary py-5">Tidak ada data laporan pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if ($type === 'reward-punishment' || $type === 'all')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Reward & Punishment</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Nilai</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rewards as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->employee?->user?->name }}</td>
                                <td>
                                    <span class="badge text-bg-success">Reward</span>
                                </td>
                                <td>{{ $item->judul }}</td>
                                <td>Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                <td>{{ $item->diberikan_pada->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach @foreach ($punishments as $index => $item)
                                <tr>
                                    <td>{{ $rewards->count() + $index + 1 }}</td>
                                    <td>{{ $item->employee?->user?->name }}</td>
                                    <td>
                                        <span class="badge text-bg-danger">Punishment</span>
                                    </td>
                                    <td>{{ $item->judul }}</td>
                                    <td>
                                        {{ $item->jenis === 'points_deduction' && $item->poin > 0 ? $item->poin . ' poin' : 'Rp ' . number_format($item->nominal, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $item->dijatuhkan_pada->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach @if ($rewards->isEmpty() && $punishments->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-5">Tidak ada data laporan
                                            pada periode ini.</td>
                                    </tr>
                                @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if ($type === 'employees' || $type === 'all')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Data Karyawan</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>ID Karyawan</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Tanggal Bergabung</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="employee-thumb">
                                        @if ($item->foto)
                                            <img src="{{ asset('storage/' . $item->foto) }}"
                                            alt="Foto {{ $item->display_name }}">
                                            @else
                                            <span>
                                                {{ strtoupper(substr($item->display_name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $item->kode_karyawan }}</td>
                                <td>{{ $item->display_name }}</td>
                                <td>{{ $item->position?->nama ?? '-' }}</td>
                                <td>{{ $item->tanggal_masuk?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $item->aktif ? 'success' : 'secondary' }}">
                                        {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                </td>
                        </tr>@empty<tr>
                                <td colspan="7" class="text-center text-secondary py-5">Tidak ada data laporan pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if ($type === 'visits' || $type === 'all')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Laporan Kunjungan</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Karyawan</th>
                            <th>Nama</th>
                            <th>Judul Kunjungan</th>
                            <th>Deskripsi</th>
                            <th>Tanggal</th>
                            <th>Koordinat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->employee?->kode_karyawan }}</td>
                                <td>{{ $item->employee?->user?->name }}</td>
                                <td>{{ $item->judul }}</td>
                                <td>{{ $item->deskripsi ? \Illuminate\Support\Str::limit($item->deskripsi, 60) : '-' }}</td>
                                <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                                <td>
                                    @if(!empty($filters['karyawan_id']) && $item->lintang && $item->bujur)
                                        {{ number_format($item->lintang, 5) }}, {{ number_format($item->bujur, 5) }}
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                        </tr>@empty<tr>
                                <td colspan="7" class="text-center text-secondary py-5">Tidak ada data laporan pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var period = document.getElementById('filter-period');
        if (!period) return;
        var fields = document.querySelectorAll('[data-period-show]');
        function toggle() {
            fields.forEach(function (el) {
                var show = el.getAttribute('data-period-show').split(' ').includes(period.value);
                el.style.display = show ? '' : 'none';
                el.querySelectorAll('select, input').forEach(function (input) {
                    input.disabled = !show;
                });
            });
        }
        period.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endpush
