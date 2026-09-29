@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Penggajian</h1>
        <p class="text-secondary mb-0">Simulasi dan rekap gaji berdasarkan kehadiran.</p>
    </div>
    @if(auth()->user()->isAdmin())
        <form
            class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto"
            method="POST"
            action="{{ route('payroll.generate') }}">
            @csrf
            <input class="form-control" name="period" type="month" value="{{ $period['value'] }}" required>
            <select class="form-select" name="karyawan_id">
                <option value="">Semua karyawan</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selectedEmployee == $employee->id)>
                        {{ $employee->kode_karyawan }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-primary text-nowrap" type="submit">
                <i class="bi bi-calculator me-2">
                </i>Generate payroll</button>
        </form>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="period">Periode</label>
                <input class="form-control" id="period" name="period" type="month" value="{{ $period['value'] }}">
            </div>
            @if(auth()->user()->isAdmin())
                <div class="col-6 col-lg-3">
                    <label class="form-label small" for="karyawan_id">Karyawan</label>
                    <select class="form-select" id="karyawan_id" name="karyawan_id">
                        <option value="">Semua karyawan</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($selectedEmployee == $employee->id)>
                                {{ $employee->kode_karyawan }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">
                    <i class="bi bi-search me-2">
                    </i>Tampilkan</button>
                <a class="btn btn-light border" href="{{ route('payroll.index') }}" title="Reset">
                    <i class="bi bi-arrow-counterclockwise">
                    </i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info border-0">
    <i class="bi bi-info-circle me-2">
    </i>Periode aktif: <strong>
        {{ $period['start']->translatedFormat('F Y') }}
    </strong>. Generate ulang akan memperbarui simulasi periode yang sama.</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a
        class="btn btn-light border btn-sm"
        href="{{ route('payroll.index', array_merge(request()->except(['period', 'page']), ['period' => date('Y-m', strtotime($period['value'].'-01 -1 month'))])) }}">
    <i class="bi bi-chevron-left">
    </i> {{ $period['start']->copy()->subMonth()->translatedFormat('F Y') }}
</a>
<span class="badge text-bg-light border px-3 py-2">
    {{ $period['start']->translatedFormat('F Y') }}
</span> @if($period['value'] !== now()->format('Y-m'))
    <a
        class="btn btn-light border btn-sm"
        href="{{ route('payroll.index', array_merge(request()->except(['period', 'page']), ['period' => date('Y-m', strtotime($period['value'].'-01 +1 month'))])) }}">
    {{ $period['start']->copy()->addMonth()->translatedFormat('F Y') }} <i class="bi bi-chevron-right">
    </i>
</a>
<a class="btn btn-outline-primary btn-sm" href="{{ route('payroll.index') }}">Bulan ini</a> @endif
<span class="text-secondary small ms-auto">Periode lalu tersimpan dan dapat dibuka kembali.</span>
</div>
<div class="card border-0 shadow-sm d-none d-md-block">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Karyawan</th>
                    <th>Gaji pokok</th>
                    <th>Reward</th>
                    <th>Potongan</th>
                    <th>Gaji bersih</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr>
                        <td>
                            <strong>{{ $payroll->employee?->user?->name ?? '-' }}</strong>
                            <small class="d-block text-secondary">
                                {{ $payroll->employee?->kode_karyawan }} · {{ $payroll->employee?->position?->nama ?? 'Tanpa jabatan' }}
                            </small>
                        </td>
                        <td class="text-nowrap">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}
                            <small
                                class="d-block text-secondary">+
                                Rp
                                {{ number_format($payroll->tunjangan_kehadiran, 0, ',', '.') }}
                                tunjangan</small>
                        </td>
                        <td class="text-success text-nowrap">+ Rp {{ number_format($payroll->total_reward, 0, ',', '.') }}
                        </td>
                        <td class="text-danger text-nowrap">
                            - Rp {{ number_format($payroll->potongan_telat + $payroll->potongan_absen + $payroll->total_hukuman, 0, ',', '.') }}
                            <small class="d-block text-secondary">
                                @php($cuts = [])
                                @if($payroll->potongan_telat > 0)
                                    @php($cuts[] = 'Terlambat')
                                @endif
                                @if($payroll->potongan_absen > 0)
                                    @php($cuts[] = 'Alpa')
                                @endif
                                @if($payroll->total_hukuman > 0)
                                    @php($cuts[] = 'Punishment')
                                @endif
                                {{ $cuts ? implode(', ', $cuts) : 'Tanpa potongan' }}
                            </small>
                        </td>
                        <td>
                            <strong class="fs-6 text-nowrap">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td>
                            @if($payroll->status === 'paid')
                                <span class="badge text-bg-success">Dibayar</span>
                            @elseif($payroll->status === 'verified')
                                <span class="badge text-bg-primary">Diverifikasi</span>
                            @else
                                <span class="badge text-bg-warning">Draft</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.show', $payroll) }}" title="Lihat slip">
                                <i class="bi bi-receipt me-1"></i>Slip</a>
                            @if(auth()->user()->isAdmin())
                                <form
                                    method="POST"
                                    action="{{ route('payroll.status', $payroll) }}"
                                    class="d-inline-flex gap-1 ms-1">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="draft" @selected($payroll->status === 'draft')>Draft</option>
                                        <option
                                            value="verified"
                                            @selected($payroll->status === 'verified')>Verified</option>
                                        <option value="paid" @selected($payroll->status === 'paid')>Paid</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Simpan status">
                                        <i class="bi bi-check">
                                        </i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-wallet2 d-block fs-2 mb-2">
                            </i>Belum ada payroll untuk periode ini. Admin dapat menekan Generate payroll.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $payrolls->links() }}</div>
</div>

<div class="d-md-none">
    @forelse($payrolls as $payroll)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <strong class="d-block">{{ $payroll->employee?->user?->name ?? '-' }}</strong>
                        <small class="text-secondary">
                            {{ $payroll->employee?->kode_karyawan }} · {{ $payroll->employee?->position?->nama ?? 'Tanpa jabatan' }}
                        </small>
                    </div>
                    @if($payroll->status === 'paid')
                        <span class="badge text-bg-success">Dibayar</span>
                    @elseif($payroll->status === 'verified')
                        <span class="badge text-bg-primary">Diverifikasi</span>
                    @else
                        <span class="badge text-bg-warning">Draft</span>
                    @endif
                </div>
                <div class="bg-light rounded p-3 mb-3">
                    <small class="text-secondary">Gaji bersih</small>
                    <strong class="d-block fs-5">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Gaji pokok</span>
                    <strong class="small">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}
                    </strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Tunjangan</span>
                    <strong class="small">+ Rp {{ number_format($payroll->tunjangan_kehadiran, 0, ',', '.') }}
                    </strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Reward</span>
                    <strong class="small text-success">+ Rp {{ number_format($payroll->total_reward, 0, ',', '.') }}
                    </strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary small">Potongan</span>
                    <strong class="small text-danger">- Rp {{ number_format($payroll->potongan_telat + $payroll->potongan_absen + $payroll->total_hukuman, 0, ',', '.') }}
                    </strong>
                </div>
                <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('payroll.show', $payroll) }}">
                    <i class="bi bi-receipt me-2"></i>Lihat Slip</a>
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('payroll.status', $payroll) }}" class="d-flex gap-2 mt-3">
                        @csrf
                        @method('PATCH')
                        <select class="form-select" name="status">
                            <option value="draft" @selected($payroll->status === 'draft')>Draft</option>
                            <option value="verified" @selected($payroll->status === 'verified')>Verified</option>
                            <option value="paid" @selected($payroll->status === 'paid')>Paid</option>
                        </select>
                        <button class="btn btn-outline-primary" type="submit" title="Simpan status">
                            <i class="bi bi-check">
                            </i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-secondary py-5">
                <i class="bi bi-wallet2 d-block fs-2 mb-2">
                </i>Belum ada payroll untuk periode ini. Admin dapat menekan Generate payroll.</div>
        </div>
    @endforelse
    <div class="mt-3">{{ $payrolls->links() }}</div>
</div>
@endsection
