@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('dashboard') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Dashboard</a>
    <h1 class="h3 mt-3 mb-1">Hari Libur</h1>
    <p
        class="text-secondary mb-0">Daftar
        libur
        nasional/cuti
        bersama.
        Tanggal
        di
        sini
        otomatis
        dikecualikan
        dari
        hitungan
        hari
        kerja
        di
        laporan
        dan
        penggajian.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center gap-2">
                <h2 class="h5 mb-0 me-auto">
                    <i class="bi bi-calendar-event me-2 text-primary">
                    </i>Daftar Libur {{ $year }}
                </h2>
                <form method="GET" class="d-flex gap-2">
                    <select class="form-select form-select-sm" name="year" onchange="this.form.submit()">
                        @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($holidays as $holiday)
                    <tr>
                        <td>
                            <strong>
                                {{ $holiday->tanggal->translatedFormat('d F Y') }}
                            </strong>
                        </td>
                        <td class="text-secondary">{{ $holiday->tanggal->translatedFormat('l') }}</td>
                        <td>{{ $holiday->nama }}</td>
                        <td class="text-end text-nowrap">
                            <button
                                class="btn btn-sm btn-outline-primary"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#editHoliday-{{ $holiday->id }}"
                                title="Ubah">
                            <i class="bi bi-pencil">
                            </i>
                            </button>
                            <form class="d-inline" method="POST" action="{{ route('holidays.destroy', $holiday) }}" data-confirm="Hapus libur {{ $holiday->nama }} ({{ $holiday->tanggal->format('d/m/Y') }})?">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                    <i class="bi bi-trash">
                                    </i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-5">
                            <i class="bi bi-calendar-x d-block fs-2 mb-2">
                            </i>Belum ada hari libur tahun {{ $year }}.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-plus-circle me-2 text-primary">
                    </i>Tambah Hari Libur</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('holidays.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="hol_date">Tanggal</label>
                        <input class="form-control @error('tanggal') is-invalid @enderror" id="hol_date" type="date" name="tanggal" value="{{ old('tanggal') }}" required>@error('tanggal')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror</div>
                    <div class="mb-3">
                        <label class="form-label" for="hol_name">Keterangan</label>
                        <input class="form-control @error('nama') is-invalid @enderror" id="hol_name" name="nama" value="{{ old('nama') }}" required maxlength="100" placeholder="cth. Hari Raya Idul Fitri">@error('nama')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror</div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-save me-2">
                        </i>Simpan Hari Libur</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($holidays as $holiday)
<div class="modal fade" id="editHoliday-{{ $holiday->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('holidays.update', $holiday) }}">@csrf @method('PUT')
        <div class="modal-header">
            <h5 class="modal-title">Ubah Hari Libur</h5>
            <button class="btn-close" type="button" data-bs-dismiss="modal">
            </button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Tanggal</label>
                <input
                    class="form-control"
                    type="date"
                    name="tanggal"
                    value="{{ $holiday->tanggal->format('Y-m-d') }}"
                    required>
            </div>
            <div class="mb-3">
                <label class="form-label">Keterangan</label>
                <input class="form-control" name="nama" value="{{ $holiday->nama }}" required maxlength="100">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>
    </div>
</div>
@endforeach
@endsection
