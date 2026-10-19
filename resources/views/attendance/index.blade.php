@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Data Kehadiran</h1>
            <p class="text-secondary mb-0">Pantau catatan masuk, pulang, dan keterlambatan.</p>
        </div>
        @unless (auth()->user()->isAdmin())
            <a class="btn btn-primary" href="{{ route('dashboard') }}">
                <i class="bi bi-plus-circle me-2">
                </i>Absensi hari ini</a>
            @endunless @if (auth()->user()->isAdmin())
                <a class="btn btn-outline-primary" href="{{ route('attendance.settings') }}">
                    <i
                        class="bi bi-gear me-2">
                        </i>Pengaturan</a> <a class="btn btn-outline-success"
                    href="{{ route('attendance.locations.index') }}">
                    <i class="bi bi-geo-alt me-2">
                    </i>Lokasi</a> <a
                    class="btn btn-primary" href="{{ route('attendance.corrections.create') }}">
                    <i
                        class="bi bi-pencil-square me-2">
                        </i>Input Manual</a>
            @endif
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-lg-3">
                        <label class="form-label small">Cari karyawan</label>
                        <input
                            class="form-control" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Nama atau kode">
                            </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small">Dari tanggal</label>
                        <input class="form-control"
                            name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small">Sampai tanggal</label>
                        <input
                            class="form-control" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small">Status</label>
                        <select class="form-select"
                            name="status">
                            <option value="">Semua status</option>
                            @foreach (['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'permission' => 'Izin', 'sick' => 'Sakit', 'duty' => 'Dinas Keluar', 'absent' => 'Alpa'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="col-6 col-lg-2">
                            <label class="form-label small">Karyawan</label>
                            <select class="form-select"
                                name="karyawan_id">
                                <option value="">Semua karyawan</option>
                                @foreach ($employees as $employee)
                                    <option
                                        value="{{ $employee->id }}"
                                        @selected((string) ($filters['karyawan_id'] ?? '') === (string) $employee->id)>
                                        {{ $employee->kode_karyawan }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-6 col-lg-2">
                        <label class="form-label small">Status Pulang</label>
                        <select class="form-select" name="status_pulang">
                            <option value="">Semua</option>
                            @foreach (['waiting' => 'Menunggu', 'approved' => 'Diizinkan', 'rejected' => 'Ditolak', 'checked_out' => 'Sudah Pulang'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status_pulang'] ?? '') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-1 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            <i
                                class="bi bi-search">
                                </i>
                                </button>
                                <a class="btn btn-light border"
                            href="{{ route('attendance.index') }}">
                            <i class="bi bi-arrow-counterclockwise">
                            </i>
                            </a>
                            </div>
                </form>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <a class="btn btn-light border btn-sm"
                href="{{ route('attendance.index', array_merge(request()->except(['date_from', 'date_to', 'month', 'page']), ['month' => $monthNav['prev']])) }}">
                <i
                    class="bi bi-chevron-left">
                    </i> {{ $monthNav['prevLabel'] }}
                    </a>
                    <span
                class="badge text-bg-light border px-3 py-2">{{ $monthNav['label'] }}</span>
            @if (!$monthNav['isCurrent'])
                <a class="btn btn-light border btn-sm"
                    href="{{ route('attendance.index', array_merge(request()->except(['date_from', 'date_to', 'month', 'page']), ['month' => $monthNav['next']])) }}">
                    {{ $monthNav['nextLabel'] }}
                    <i class="bi bi-chevron-right">
                    </i>
                    </a>
                    <a class="btn btn-outline-primary btn-sm"
                    href="{{ route('attendance.index') }}">Bulan ini</a>
            @endif
            <span class="text-secondary small ms-auto">
                Data bulan lalu tersimpan dan dapat dibuka kembali.</span>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Karyawan</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Total kerja</th>
                            <th>Terlambat</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Lokasi</th>
                            @if (auth()->user()->isAdmin())
                                <th>Status Pulang</th>
                            <th>Aksi</th> @else
                            <th>
                            </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $attendance->employee?->user?->name ?? '-' }}
                                    </strong>
                                    <small
                                        class="d-block text-secondary">
                                        {{ $attendance->employee?->kode_karyawan }}
                                        </small>
                                        </td>
                                <td>{{ $attendance->tanggal->format('d M Y') }}</td>
                                <td>{{ $attendance->waktu_masuk?->format('H:i') ?? '-' }}</td>
                                <td>{{ $attendance->waktu_pulang?->format('H:i') ?? '-' }}</td>
                                <td>
                                    {{ intdiv($attendance->menit_kerja, 60) }}j {{ $attendance->menit_kerja % 60 }}m</td>
                                <td>{{ $attendance->menit_telat }} menit</td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $attendance->status === 'late' ? 'warning' : ($attendance->status === 'present' ? 'success' : 'secondary') }}">
                                        {{ ['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'permission' => 'Izin', 'sick' => 'Sakit', 'duty' => 'Dinas Keluar', 'absent' => 'Alpa'][$attendance->status] ?? ucfirst($attendance->status) }}
                                        </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if ($attendance->foto_masuk)
                                            <a href="{{ route('attendance.show', $attendance) }}" title="Foto masuk">
                                                <img
                                                     src="{{ asset('storage/' . $attendance->foto_masuk) }}" width="32"
                                                    height="32" class="rounded border" style="object-fit:cover"
                                                    alt="Masuk">
                                                    </a>
                                            @endif @if ($attendance->foto_pulang)
                                                <a
                                                    href="{{ route('attendance.show', $attendance) }}"
                                                    title="Foto pulang">
                                                <img
                                                         src="{{ asset('storage/' . $attendance->foto_pulang) }}"
                                                        width="32" height="32" class="rounded border"
                                                        style="object-fit:cover" alt="Pulang">
                                                        </a>
                                            @endif @unless ($attendance->foto_masuk || $attendance->foto_pulang)
                                            @if ($attendance->waktu_masuk)
                                                <span
                                                    class="badge text-bg-info"
                                                    title="Dicatat manual oleh admin tanpa foto/GPS">Manual</span>
                                            @else
                                                <span class="text-secondary">-</span>
                                            @endif
                                        @endunless
                                </div>
                            </td>
                            <td>
                                @if ($attendance->lintang_masuk && $attendance->bujur_masuk)
                                    @if ($attendance->lokasi_masuk_valid === true)
                                        <span class="badge text-bg-success">
                                            <i class="bi bi-geo-alt me-1">
                                            </i>Sesuai</span>
                                    @elseif($attendance->lokasi_masuk_valid === false)
                                        <span class="badge text-bg-danger">
                                            <i class="bi bi-geo-alt me-1">
                                            </i>Di luar
                                        area</span> @else
                                        <span class="badge text-bg-secondary">
                                            <i
                                                class="bi bi-geo-alt me-1">
                                                </i>Tercatat</span>
                                @endif @else<span class="text-secondary">-</span>
                                @endif
                            </td>
                            @if (auth()->user()->isAdmin())
                                <td>
                                    @if ($attendance->waktu_pulang)
                                        <span class="badge text-bg-success">
                                            <i class="bi bi-check-circle me-1">
                                            </i>Sudah
                                            Pulang</span>
                                    @elseif($attendance->status_pulang === 'approved')
                                        <span class="badge text-bg-success">
                                            <i
                                                class="bi bi-check-circle me-1">
                                                </i>Diizinkan
                                            {{ \App\Services\CheckoutPolicy::TYPE_LABELS[$attendance->tipe_persetujuan_pulang] ?? '' }}
                                            </span>
                                    @elseif($attendance->status_pulang === 'rejected')
                                    <span class="badge text-bg-danger">Ditolak</span> @else<span
                                            class="badge text-bg-secondary">Belum Pulang</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($attendance->tanggal->isToday() && !$attendance->waktu_pulang)
                                        @if ($attendance->status_pulang === 'approved')
                                        <span class="badge text-bg-success">Diizinkan</span> @else<form
                                                class="d-inline" method="POST"
                                                action="{{ route('attendance.approval', $attendance) }}"> @csrf
                                                @method('PATCH')<input type="hidden" name="decision"
                                                    value="approved">
                                                    <input type="hidden" name="type"
                                                    value="early">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                    type="submit" title="Izinkan Pulang Lebih Awal">Awal</button>
                                                    </form>
                                            <form class="d-inline" method="POST"
                                                action="{{ route('attendance.approval', $attendance) }}"> @csrf
                                                @method('PATCH')<input type="hidden" name="decision"
                                                    value="approved">
                                                    <input type="hidden" name="type"
                                                    value="special">
                                                    <button class="btn btn-sm btn-outline-info"
                                                    type="submit" title="Izin Khusus">Khusus</button>
                                                    </form>
                                        @endif
                                        <form class="d-inline" method="POST"
                                            action="{{ route('attendance.approval', $attendance) }}"> @csrf
                                            @method('PATCH')<input type="hidden" name="decision"
                                                value="rejected">
                                                <button class="btn btn-sm btn-outline-danger"
                                                type="submit" title="Tahan / Tolak">Tahan</button>
                                                </form>
                                    @endif
                                    <a class="btn btn-sm btn-outline-secondary ms-1"
                                        href="{{ route('attendance.show', $attendance) }}" title="Detail absensi">
                                        <i class="bi bi-eye">
                                        </i>
                                        </a>
                                    <a class="btn btn-sm btn-outline-warning ms-1"
                                        href="{{ route('attendance.corrections.edit', $attendance) }}" title="Koreksi absensi">
                                        <i class="bi bi-pencil">
                                        </i>
                                        </a>
                            </td> @else
                            <td>
                                <a class="btn btn-sm btn-outline-secondary"
                                        href="{{ route('attendance.show', $attendance) }}" title="Detail absensi">
                                        <i
                                            class="bi bi-eye">
                                            </i>
                                            </a>
                                            </td>
                            @endif
                        </tr>
                    @empty<tr>
                            <td colspan="11" class="text-center text-secondary py-5">
                                <i
                                    class="bi bi-calendar-x d-block fs-2 mb-2">
                                    </i>Belum ada data kehadiran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $attendances->links() }}</div>
    </div>
@endsection
