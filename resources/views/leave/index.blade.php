@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Cuti, Sakit & Dinas Keluar</h1>
        <p class="text-secondary mb-0">Kelola pengajuan, persetujuan, dan riwayat cuti, sakit, serta dinas keluar karyawan.</p>
    </div>
    @unless(auth()->user()->isAdmin())
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveModal">
            <i class="bi bi-plus-lg me-2">
            </i>Buat pengajuan</button>
    @endunless
</div>

@if($stats)
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <small class="text-secondary">Total Pengajuan</small>
                        <div class="fs-3 fw-bold mt-1">
                            {{ $stats['total'] }}
                        </div>
                    </div>
                    <i class="icon bi bi-clipboard-data">
                    </i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100" style="border-left-color:var(--bs-warning)">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <small class="text-secondary">Menunggu Persetujuan</small>
                        <div class="fs-3 fw-bold mt-1">
                            {{ $stats['pending'] }}
                        </div>
                    </div>
                    <i class="icon bi bi-clock text-warning">
                    </i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100" style="border-left-color:var(--bs-success)">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <small class="text-secondary">Disetujui</small>
                        <div class="fs-3 fw-bold mt-1">
                            {{ $stats['approved'] }}
                        </div>
                    </div>
                    <i class="icon bi bi-check-circle text-success">
                    </i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100" style="border-left-color:var(--bs-danger)">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <small class="text-secondary">Ditolak</small>
                        <div class="fs-3 fw-bold mt-1">
                            {{ $stats['rejected'] }}
                        </div>
                    </div>
                    <i class="icon bi bi-x-circle text-danger">
                    </i>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small" for="search">Cari Karyawan</label>
                <input
                    class="form-control"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Cari nama atau ID karyawan...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="jenis">Jenis</label>
                <select class="form-select" id="jenis" name="jenis">
                    <option value="">Semua Jenis</option>
                    <option value="leave" @selected(($filters['jenis'] ?? '') === 'leave')>Cuti</option>
                    <option value="sick" @selected(($filters['jenis'] ?? '') === 'sick')>Sakit</option>
                    <option value="duty" @selected(($filters['jenis'] ?? '') === 'duty')>Dinas Keluar</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Menunggu</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Disetujui</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Ditolak</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small" for="date_from">Mulai</label>
                <input
                    class="form-control"
                    id="date_from"
                    type="date"
                    name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small" for="date_to">Selesai</label>
                <input
                    class="form-control"
                    id="date_to"
                    type="date"
                    name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">
                    <i class="bi bi-search me-2">
                    </i>Cari</button>
                <a class="btn btn-light border" href="{{ route('leave.index') }}" title="Reset">
                    <i class="bi bi-arrow-counterclockwise">
                    </i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Karyawan</th>
                    <th>Jenis</th>
                    <th>Periode</th>
                    <th>Hari</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $index => $item)
                    <tr>
                        <td>{{ $requests->firstItem() + $index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-thumb">
                                    @if($item->employee?->foto)
                                        <img
                                            src="{{ asset('storage/'.$item->employee->foto) }}"
                                            alt="Foto {{ $item->employee?->display_name }}">
                                    @else
                                        <span>
                                            {{ strtoupper(substr($item->employee?->display_name ?? '-', 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <strong class="d-block">{{ $item->employee?->display_name ?? '-' }}</strong>
                                    <small class="text-secondary">{{ $item->employee?->kode_karyawan ?? '-' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($item->jenis === 'leave')
                                <span class="badge text-bg-primary">Cuti</span>
                            @elseif($item->jenis === 'permission')
                                <span class="badge text-bg-warning">Izin</span>
                            @elseif($item->jenis === 'duty')
                                <span class="badge text-bg-success">Dinas Keluar</span>
                            @else
                                <span class="badge text-bg-info">Sakit</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            {{ $item->tanggal_mulai->format('d M Y') }} - {{ $item->tanggal_selesai->format('d M Y') }}
                        </td>
                        <td>{{ $item->jumlah_hari }} Hari</td>
                        <td>
                            @if($item->status === 'approved')
                                <span class="badge text-bg-success">Disetujui</span>
                            @elseif($item->status === 'rejected')
                                <span class="badge text-bg-danger">Ditolak</span>
                            @else
                                <span class="badge text-bg-warning">Menunggu</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('leave.show', $item) }}">
                                    <i class="bi bi-eye me-1">
                                    </i>Detail</a>
                                @if($item->status === 'pending' && !auth()->user()->isAdmin())
                                    <form
                                        method="POST"
                                        action="{{ route('leave.destroy', $item) }}"
                                        onsubmit="return confirm('Batalkan pengajuan ini? Data akan dihapus permanen.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit">
                                            <i class="bi bi-trash me-1">
                                            </i>Batalkan</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-inbox d-block fs-2 mb-2">
                            </i>Belum ada pengajuan cuti atau izin.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $requests->links() }}</div>
</div>

@unless(auth()->user()->isAdmin())
    <div class="modal fade" id="leaveModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Pengajuan baru</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button">
                    </button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Jenis pengajuan</label>
                    <select class="form-select mb-3" name="jenis" required>
                        <option value="leave">Cuti</option>
                        <option value="sick">Sakit</option>
                        <option value="duty">Dinas Keluar</option>
                    </select>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Mulai</label>
                            <input class="form-control" type="date" name="tanggal_mulai" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Selesai</label>
                            <input class="form-control" type="date" name="tanggal_selesai" required>
                        </div>
                    </div>
                    <div
                        class="form-text mb-3">Pengajuan
                        <strong>cuti</strong>
                        dapat dilakukan mendadak (tanpa batas H-7),
                        maksimal 3 hari berturut-turut. Kuota 12 hari/tahun.
                        <strong>Dinas keluar</strong> tidak memotong kuota dan tidak perlu absen.</div>
                    <label class="form-label">Alasan</label>
                    <textarea class="form-control mb-3" name="alasan" rows="3" required>
                    </textarea>
                    <label class="form-label">Dokumen pendukung</label>
                    <input class="form-control" type="file" name="lampiran">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Kirim pengajuan</button>
                </div>
            </form>
        </div>
    </div>
@endunless
@endsection
