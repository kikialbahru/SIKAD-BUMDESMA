@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="text-secondary mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div><span
            class="badge rounded-pill text-bg-light border px-3 py-2">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Karyawan' }}</span>
    </div>
    @if ($adminStats)
        <div class="row g-3 mb-4">
            @foreach ([['Total Karyawan', $adminStats['employees'], 'bi-people'], ['Hadir Hari Ini', $adminStats['present'], 'bi-person-check'], ['Terlambat', $adminStats['late'], 'bi-alarm'], ['Pengajuan Menunggu', $adminStats['pendingLeaves'], 'bi-hourglass-split']] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex justify-content-between">
                            <div><small class="text-secondary">{{ $stat[0] }}</small>
                                <div class="fs-3 fw-bold mt-1">{{ $stat[1] }}</div>
                            </div><i class="icon bi {{ $stat[2] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if ($adminStats)
        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-1">Tren kehadiran</h2>
                                <p class="text-secondary small mb-0">Hadir vs terlambat, 6 bulan terakhir.</p>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('reports.index', ['type' => 'attendance']) }}">
                                <i class="bi bi-bar-chart me-1"></i>Laporan</a>
                        </div>
                        <div style="height:280px"><canvas id="attendanceChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 d-flex flex-column gap-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-1">Sering terlambat</h2>
                        <p class="text-secondary small mb-3">Bulan {{ now()->translatedFormat('F Y') }} · 5 teratas</p>
                        @forelse ($adminStats['topLate'] as $row)
                            <div class="d-flex align-items-center gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                <div class="employee-thumb flex-shrink-0" style="width:38px;height:38px;font-size:1rem">
                                    @if($row->employee?->foto)
                                        <img src="{{ asset('storage/'.$row->employee->foto) }}" alt="Foto {{ $row->employee->nama }}">
                                    @else
                                        <span>{{ strtoupper(mb_substr($row->employee->nama ?? '?', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex-grow-1" style="min-width:0">
                                    <strong class="d-block text-truncate">{{ $row->employee->nama ?? 'Karyawan dihapus' }}</strong>
                                    <small class="text-secondary">{{ $row->employee->kode_karyawan ?? '-' }} · total {{ $row->late_minutes }} mnt</small>
                                </div>
                                <span class="badge text-bg-warning flex-shrink-0">{{ $row->late_count }}× telat</span>
                            </div>
                        @empty
                            <p class="text-secondary small mb-0">
                                <i class="bi bi-check-circle me-1"></i>Tidak ada keterlambatan bulan ini.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h2 class="h5 mb-0">Belum absen hari ini</h2>
                            <span class="badge {{ $adminStats['notCheckedIn']->isEmpty() ? 'text-bg-success' : 'text-bg-warning' }}">
                                {{ $adminStats['notCheckedIn']->count() }} orang</span>
                        </div>
                        <p class="text-secondary small mb-3">Karyawan aktif tanpa absen masuk tercatat.</p>
                        @forelse ($adminStats['notCheckedIn'] as $absent)
                            @php($wa = preg_replace('/\D/', '', $absent->telepon ?? ''))
                            @php($wa = $wa ? (str_starts_with($wa, '0') ? '62'.substr($wa, 1) : $wa) : null)
                            <div class="d-flex align-items-center gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                <div class="flex-grow-1" style="min-width:0">
                                    <strong class="d-block text-truncate">{{ $absent->display_name }}</strong>
                                </div>
                                @if($wa)
                                    <a class="btn btn-sm btn-outline-success flex-shrink-0" target="_blank" rel="noopener"
                                        href="https://wa.me/{{ $wa }}?text={{ urlencode('Halo '.$absent->display_name.', Anda belum tercatat absen masuk hari ini ('.now()->translatedFormat('d F Y').'). Mohon segera lakukan absensi.') }}">
                                        <i class="bi bi-whatsapp me-1"></i>Ingatkan</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-secondary small mb-0">
                                <i class="bi bi-check-circle me-1"></i>Semua karyawan sudah absen.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Menunggu persetujuan</h2>
                        <p class="text-secondary small mb-0">Pengajuan cuti & sakit terbaru yang belum diproses.</p>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('leave.index') }}">Lihat semua</a>
                </div>
                @if($adminStats['pendingApprovals']->isEmpty())
                    <p class="text-secondary small mb-0">
                        <i class="bi bi-check-circle me-1"></i>Tidak ada pengajuan menunggu.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Jenis</th>
                                    <th>Periode</th>
                                    <th>Diajukan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($adminStats['pendingApprovals'] as $approval)
                                    <tr>
                                        <td>
                                            <strong class="d-block">{{ $approval->employee->nama ?? 'Karyawan dihapus' }}</strong>
                                            <small class="text-secondary">{{ $approval->employee->kode_karyawan ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $approval->jenis === 'sick' ? 'text-bg-info' : 'text-bg-primary' }}">
                                                {{ $approval->jenis === 'sick' ? 'Sakit' : 'Cuti' }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $approval->tanggal_mulai->translatedFormat('d M') }} – {{ $approval->tanggal_selesai->translatedFormat('d M Y') }}</small>
                                            <small class="text-secondary d-block">{{ $approval->jumlah_hari }} hari</small>
                                        </td>
                                        <td><small class="text-secondary">{{ $approval->created_at->diffForHumans() }}</small></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-primary" href="{{ route('leave.show', $approval) }}">Tinjau</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
    <div class="row g-4">
        @unless (auth()->user()->isAdmin())
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="h5 mb-1">Absensi hari ini</h2>
                                <p class="text-secondary small mb-0">Gunakan waktu server sebagai catatan resmi.</p>
                            </div><span
                                class="badge {{ $attendance?->status === 'late' ? 'text-bg-warning' : ($attendance?->waktu_masuk ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $attendance?->status === 'late' ? 'Terlambat' : ($attendance?->waktu_masuk ? 'Hadir' : 'Belum absen') }}</span>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Masuk</small><strong
                                        class="d-block fs-4">{{ $attendance?->waktu_masuk?->format('H:i') ?? '--:--' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Pulang</small><strong
                                        class="d-block fs-4">{{ $attendance?->waktu_pulang?->format('H:i') ?? '--:--' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Keterlambatan</small><strong
                                        class="d-block fs-4">{{ $attendance?->menit_telat ?? 0 }} <small>mnt</small></strong>
                                </div>
                            </div>
                        </div>
                        @if ($attendance?->waktu_masuk)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="text-secondary small">Bukti
                                    masuk:</span>
                                @if ($attendance->foto_masuk)
                                    <a href="{{ route('attendance.show', $attendance) }}"><img
                                            src="{{ asset('storage/' . $attendance->foto_masuk) }}" width="30"
                                            height="30" class="rounded border" style="object-fit:cover"
                                            alt="Foto absen masuk"></a><span class="badge text-bg-success"><i
                                            class="bi bi-camera me-1"></i>Foto tersimpan</span>
                                    @endif @if ($attendance->lintang_masuk && $attendance->bujur_masuk)
                                        <a class="text-decoration-none"
                                            href="{{ route('attendance.show', $attendance) }}"><span
                                                class="badge {{ $attendance->lokasi_masuk_valid === false ? 'text-bg-danger' : 'text-bg-success' }}"><i
                                                    class="bi bi-geo-alt me-1"></i>{{ $attendance->lokasi_masuk_valid === false ? 'Di luar area' : ($attendance->lokasi_masuk_valid === true ? 'Lokasi sesuai' : 'Lokasi tercatat') }}</span></a>
                                    @endif
                            </div>
                            @endif @if ($attendance?->waktu_masuk && !$attendance?->waktu_pulang)
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span
                                        class="text-secondary small">Status Pulang:</span>
                                    @if (($checkout['state'] ?? '') === 'allowed')
                                        <span class="badge text-bg-success">Siap Pulang</span>
                                    @elseif(($checkout['state'] ?? '') === 'rejected')
                                        <span class="badge text-bg-danger">Ditolak</span>
                                    @elseif(($checkout['state'] ?? '') === 'too_early')
                                        <span class="badge text-bg-secondary">Belum Waktu Pulang</span>
                                    @elseif(($checkout['state'] ?? '') === 'too_late')
                                    <span class="badge text-bg-secondary">Rentang Berakhir</span> @else<span
                                            class="badge text-bg-warning">Menunggu Persetujuan</span>
                                        @endif @if ($times)
                                            <span class="text-secondary small">Absensi pulang
                                                {{ $times['window_start']->format('H:i') }}–{{ $times['window_end']->format('H:i') }}</span>
                                        @endif
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                @if (!$attendance?->waktu_masuk)
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#checkInModal"><i class="bi bi-camera me-2"></i>Absen Masuk</button>
                                @elseif(!$attendance?->waktu_pulang)
                                    @if ($checkout['allowed'] ?? false)
                                        <button class="btn btn-success" type="button" data-bs-toggle="modal"
                                            data-bs-target="#checkOutModal"><i class="bi bi-camera me-2"></i>Absen
                                        Pulang</button> @else<button class="btn btn-secondary" type="button" disabled><i
                                                class="bi bi-lock me-2"></i>Absen Pulang</button>
                                @endif @else<span class="btn btn-light border"><i
                                            class="bi bi-check-circle text-success me-2"></i>Absensi hari ini lengkap</span>
                                @endif
                            </div>
                            @if ($attendance?->waktu_masuk && !$attendance?->waktu_pulang)
                                <div class="form-text mt-2">{{ $checkout['reason'] ?? '' }}</div>
                            @endif
                    </div>
                </div>
        </div>@else<div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-1">Mode Administrator</h2>
                        <p class="text-secondary small mb-0">Absensi hanya untuk karyawan. Pantau kehadiran seluruh karyawan
                            melalui menu Kehadiran atau Laporan.</p><a class="btn btn-primary mt-3"
                            href="{{ route('attendance.index') }}"><i class="bi bi-calendar-check me-2"></i>Pantau
                            Kehadiran</a>
                    </div>
                </div>
            </div>
        @endunless
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Ringkasan bulan ini</h2>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span
                            class="text-secondary">Kehadiran</span><strong>{{ $presentCount }} hari</strong></div>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span
                            class="text-secondary">Keterlambatan</span><strong>{{ $lateCount }} kali</strong></div>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span class="text-secondary">Cuti
                            disetujui</span><strong>{{ $leaveCount }} pengajuan</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-secondary">Kunjungan hari ini{{ auth()->user()->isAdmin() ? ' (semua karyawan)' : '' }}</span><strong>{{ $visitCount ?? 0 }} kunjungan</strong></div><a
                        href="{{ route('leave.index') }}" class="btn btn-outline-primary w-100 mt-3">Lihat cuti & izin</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('visits.index') }}" class="btn btn-primary w-100 mt-2">Lihat kunjungan</a>
                    @else
                        <a href="{{ route('visits.index') }}" class="btn btn-primary w-100 mt-2">Catat kunjungan</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @include('attendance.capture-modals')
@push('scripts')
@if ($adminStats)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const canvas = document.querySelector('#attendanceChart');
        if (!canvas || typeof Chart === 'undefined') return;
        const data = @json($adminStats['chart']);
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Hadir', data: data.present, backgroundColor: '#106fae', borderRadius: 6 },
                    { label: 'Terlambat', data: data.late, backgroundColor: '#f5c34d', borderRadius: 6 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    })();
</script>
@endif
@endpush
@endsection
