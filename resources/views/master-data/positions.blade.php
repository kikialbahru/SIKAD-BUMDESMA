@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('dashboard') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Dashboard</a>
    <h1 class="h3 mt-3 mb-1">Master Data</h1>
    <p class="text-secondary mb-0">Kelola jabatan, jadwal kerja, dan tarif potongan gaji. Data yang masih dipakai karyawan tidak dapat dihapus.</p>
</div>

@include('master-data._tabs')

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-briefcase me-2 text-primary">
                    </i>Daftar Jabatan</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Karyawan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($positions as $position)
                            <tr>
                                <td><strong>{{ $position->nama }}</strong></td>
                                <td class="text-secondary">{{ $position->deskripsi ?? '-' }}</td>
                                <td><span class="badge text-bg-light border">{{ $position->employees_count }} orang</span></td>
                                <td class="text-end text-nowrap">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPosition-{{ $position->id }}"
                                        title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form class="d-inline" method="POST" action="{{ route('positions.destroy', $position) }}" data-confirm="Hapus jabatan {{ $position->nama }}?">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus" @disabled($position->employees_count > 0)>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada jabatan.</td>
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
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Jabatan</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('positions.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="pos_name">Nama jabatan</label>
                        <input class="form-control @error('nama') is-invalid @enderror" id="pos_name" name="nama" value="{{ old('nama') }}" required maxlength="100" placeholder="cth. Staff">@error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pos_desc">Deskripsi (opsional)</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="pos_desc" name="deskripsi" rows="2" maxlength="500">{{ old('deskripsi') }}</textarea>@error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-save me-2"></i>Simpan Jabatan</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($positions as $position)
<div class="modal fade" id="editPosition-{{ $position->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('positions.update', $position) }}">@csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Jabatan</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama jabatan</label>
                    <input class="form-control" name="nama" value="{{ $position->nama }}" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi (opsional)</label>
                    <textarea class="form-control" name="deskripsi" rows="2" maxlength="500">{{ $position->deskripsi }}</textarea>
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
