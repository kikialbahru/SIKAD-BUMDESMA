@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('dashboard') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Dashboard</a>
    <h1 class="h3 mt-3 mb-1">Master Data</h1>
    <p class="text-secondary mb-0">Tarif potongan per karyawan untuk perhitungan gaji. Tanpa tarif khusus, dipakai bawaan: Rp1.000/menit telat dan Rp0/hari absen. Tarif nonaktif diabaikan.</p>
</div>

@include('master-data._tabs')

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small" for="comp_employee">Karyawan</label>
                <select class="form-select" id="comp_employee" name="karyawan_id" onchange="this.form.submit()">
                    @forelse($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($selected?->id === $employee->id)>{{ $employee->kode_karyawan }} - {{ $employee->display_name }}</option>
                    @empty
                        <option value="">Belum ada karyawan aktif</option>
                    @endforelse
                </select>
            </div>
        </form>
    </div>
</div>

@if($selected)
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-cash-coin me-2 text-primary"></i>Tarif {{ $selected->kode_karyawan }} - {{ $selected->display_name }}</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($components as $component)
                            <tr>
                                <td><strong>{{ $types[$component->jenis] ?? $component->jenis }}</strong></td>
                                <td class="text-secondary">{{ $component->nama }}</td>
                                <td class="text-nowrap">Rp{{ number_format($component->nominal, 0, ',', '.') }}</td>
                                <td>
                                    @if($component->aktif)
                                        <span class="badge text-bg-success">Aktif</span>
                                    @else
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editComponent-{{ $component->id }}"
                                        title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form class="d-inline" method="POST" action="{{ route('salary-components.destroy', $component) }}" data-confirm="Hapus tarif {{ $types[$component->jenis] ?? $component->jenis }} milik {{ $selected->display_name }}? Perhitungan kembali memakai tarif bawaan.">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada tarif khusus — dipakai tarif bawaan.</td>
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
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Tarif</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('salary-components.store') }}">
                    @csrf
                    <input type="hidden" name="karyawan_id" value="{{ $selected->id }}">
                    <div class="mb-3">
                        <label class="form-label" for="comp_type">Jenis tarif</label>
                        <select class="form-select @error('jenis') is-invalid @enderror" id="comp_type" name="jenis" required>
                            <option value="">Pilih jenis</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('jenis') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>@error('jenis')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="comp_name">Keterangan</label>
                        <input class="form-control @error('nama') is-invalid @enderror" id="comp_name" name="nama" value="{{ old('nama') }}" required maxlength="100" placeholder="cth. Tarif telat staf">@error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="comp_amount">Nominal (Rp)</label>
                        <input class="form-control @error('nominal') is-invalid @enderror" id="comp_amount" type="number" name="nominal" value="{{ old('nominal') }}" min="0" max="999999999" step="any" required>@error('nominal')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="comp_active" @checked(old('aktif', true))>
                        <label class="form-check-label" for="comp_active">Aktif</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-save me-2"></i>Simpan Tarif</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($components as $component)
<div class="modal fade" id="editComponent-{{ $component->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('salary-components.update', $component) }}">@csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Tarif</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenis tarif</label>
                    <select class="form-select" name="jenis" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected($component->jenis === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input class="form-control" name="nama" value="{{ $component->nama }}" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal (Rp)</label>
                    <input class="form-control" type="number" name="nominal" value="{{ $component->nominal }}" min="0" max="999999999" step="any" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="aktif" value="1" id="edit_comp_active_{{ $component->id }}" @checked($component->aktif)>
                    <label class="form-check-label" for="edit_comp_active_{{ $component->id }}">Aktif</label>
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
@else
<div class="alert alert-warning">Belum ada karyawan aktif. Tambahkan dulu di Data Karyawan.</div>
@endif
@endsection
