@extends('layouts.app')

@section('content')
<style>
@media print {
    .no-print { display: none !important; }
    .sidebar, .content > nav.navbar, .mobile-nav, .alert { display: none !important; }
    .content { width: 100% !important; }
    main { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
<div class="mb-4 no-print">
    <a class="text-decoration-none" href="{{ route('payroll.index', ['period' => $payroll->periode_mulai->format('Y-m')]) }}">
        <i class="bi bi-arrow-left me-2"></i>Kembali ke Penggajian</a>
    <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
        <div class="me-auto">
            <h1 class="h3 mb-1">Slip Gaji — {{ $periodLabel }}</h1>
            <p class="text-secondary mb-0">{{ $payroll->employee?->display_name ?? '-' }} · {{ $payroll->employee?->kode_karyawan ?? '-' }}</p>
        </div>
        @if($payroll->status === 'paid')
            <span class="badge text-bg-success">Dibayar</span>
        @elseif($payroll->status === 'verified')
            <span class="badge text-bg-primary">Diverifikasi</span>
        @else
            <span class="badge text-bg-warning">Draft</span>
        @endif
        <a class="btn btn-outline-danger" href="{{ route('payroll.pdf', $payroll) }}">
            <i class="bi bi-file-earmark-pdf me-2"></i>Unduh PDF</a>
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Cetak</button>
    </div>
</div>

<div class="d-none d-print-block mb-3">
    <h1 class="h4 mb-0">Slip Gaji — {{ $periodLabel }}</h1>
    <p class="mb-0">{{ $payroll->employee?->display_name ?? '-' }} · {{ $payroll->employee?->kode_karyawan ?? '-' }}</p>
</div>

@if($payroll->status === 'draft')
    <div class="alert alert-warning border-0 no-print">
        <i class="bi bi-info-circle me-2"></i>Slip ini masih <strong>draft</strong> — angka dapat berubah bila admin menjalankan ulang Generate payroll.</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="bi bi-person me-2 text-primary"></i>Data Karyawan</h2>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Nama</span><strong>{{ $payroll->employee?->display_name ?? '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">ID Karyawan</span><strong>{{ $payroll->employee?->kode_karyawan ?? '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Jabatan</span><strong>{{ $payroll->employee?->position?->nama ?? '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary">Periode</span><strong>{{ $payroll->periode_mulai->translatedFormat('d F Y') }} - {{ $payroll->periode_selesai->translatedFormat('d F Y') }}</strong>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Ringkasan Kehadiran</h2>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Hari kerja</span><strong>{{ $workdays }} hari</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Hadir</span><strong>{{ $presentDays }} hari</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Terlambat</span><strong>{{ $lateDays }} hari ({{ $lateMinutes }} menit)</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary">Absen</span><strong>{{ $absenceDays }} hari</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Penerimaan</h2>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr>
                            <td>Gaji pokok</td>
                            <td class="text-end text-nowrap">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan</td>
                            <td class="text-end text-nowrap">Rp {{ number_format($payroll->tunjangan_kehadiran, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>
                                Reward
                                @forelse($rewards as $reward)
                                    <small class="d-block text-secondary">{{ $reward->diberikan_pada?->format('d M') }} · {{ $reward->judul }}</small>
                                @empty
                                    <small class="d-block text-secondary">Tidak ada reward periode ini</small>
                                @endforelse
                            </td>
                            <td class="text-end text-nowrap text-success">+ Rp {{ number_format($payroll->total_reward, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="bi bi-dash-circle me-2 text-danger"></i>Potongan</h2>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr>
                            <td>
                                Keterlambatan
                                <small class="d-block text-secondary">{{ $lateMinutes }} menit × Rp {{ number_format($lateRate, 0, ',', '.') }}</small>
                            </td>
                            <td class="text-end text-nowrap text-danger">- Rp {{ number_format($payroll->potongan_telat, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>
                                Absen
                                <small class="d-block text-secondary">{{ $absenceDays }} hari × Rp {{ number_format($absenceRate, 0, ',', '.') }}</small>
                            </td>
                            <td class="text-end text-nowrap text-danger">- Rp {{ number_format($payroll->potongan_absen, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>
                                Hukuman
                                @forelse($punishments as $punishment)
                                    <small class="d-block text-secondary">{{ $punishment->dijatuhkan_pada?->format('d M') }} · {{ $punishment->judul }}@if($punishment->jenis === 'points_deduction' && $punishment->poin) ({{ $punishment->poin }} poin)@endif</small>
                                @empty
                                    <small class="d-block text-secondary">Tidak ada hukuman periode ini</small>
                                @endforelse
                            </td>
                            <td class="text-end text-nowrap text-danger">- Rp {{ number_format($payroll->total_hukuman, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <strong>Gaji bersih</strong>
                <strong class="fs-4">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
