@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Data Karyawan</h1>
            <p class="text-secondary mb-0">Kelola informasi karyawan BUMDESMA LKD TARUB</p>
        </div>
        <a class="btn btn-primary" href="{{ route('employees.create') }}">
            <i class="bi bi-person-plus me-2">
            </i>Tambah
            Karyawan</a>
    </div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label small">Cari nama atau ID karyawan</label>
                    <input
                        class="form-control" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Contoh: KRY-001">
                        </div>
                <div class="col-md-3">
                    <label class="form-label small">Jabatan</label>
                    <select class="form-select"
                        name="jabatan_id">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option
                                value="{{ $position->id }}"
                                @selected(($filters['jabatan_id'] ?? '') == $position->id)>
                            {{ $position->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select"
                        name="status">
                        <option value="">Semua</option>
                        <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                        <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                    </select>
                    </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit">
                        <i
                            class="bi bi-search me-2">
                            </i>Cari</button>
                            <a class="btn btn-light border ms-1"
                        href="{{ route('employees.index') }}" title="Reset">
                        <i
                            class="bi bi-arrow-counterclockwise">
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
                        <th>Foto</th>
                        <th>ID Karyawan</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Akun Login</th>
                        <th>Tanggal Bergabung</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $employee)
                        <tr>
                            <td>{{ $employees->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                    <div class="employee-thumb">
                                        @if ($employee->foto)
                                            <img src="{{ asset('storage/' . $employee->foto) }}"
                                            alt="Foto {{ $employee->display_name }}">
                                            @else
                                            <span>
                                                {{ strtoupper(substr($employee->display_name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                                </td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}"
                                    class="text-decoration-none">
                                    <strong>
                                        {{ $employee->kode_karyawan }}
                                    </strong>
                                    </a>
                                    </td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}"
                                    class="text-decoration-none">
                                    <strong>
                                        {{ $employee->display_name }}
                                    </strong>
                                    </a>
                                    <small
                                    class="d-block text-secondary">
                                    {{ $employee->display_email ?? 'Email tidak tersedia' }}
                                    </small>
                            </td>
                            <td>{{ $employee->position?->nama ?? '-' }}</td>
                            <td>
                                @if ($employee->user)
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-check-circle me-1">
                                        </i>Akun
                                    Aktif</span>
                                    @else
                                    <span class="badge text-bg-secondary">
                                        <i
                                            class="bi bi-dash-circle me-1">
                                            </i>Belum Dibuat</span>
                                @endif
                            </td>
                            <td>{{ $employee->tanggal_masuk?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <span
                                    class="badge text-bg-{{ $employee->aktif ? 'success' : 'secondary' }}">
                                    {{ $employee->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('employees.show', $employee) }}" title="Lihat">
                                    <i
                                        class="bi bi-eye">
                                        </i>
                                        </a> <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('employees.edit', $employee) }}" title="Edit">
                                    <i
                                        class="bi bi-pencil">
                                        </i>
                                        </a>
                                @if (!$employee->user)
                                    <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal"
                                        data-bs-target="#accountModal-{{ $employee->id }}" title="Buat Akun">
                                        <i
                                            class="bi bi-person-plus">
                                            </i>
                                            </button>
                                    @endif @if ($employee->aktif)
                                        <form class="d-inline" method="POST"
                                            action="{{ route('employees.destroy', $employee) }}"
                                            data-confirm="Data karyawan akan dinonaktifkan. Lanjutkan?">@csrf
                                            @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"
                                                title="Hapus/nonaktifkan">
                                                <i class="bi bi-trash">
                                                </i>
                                                </button>
                                                </form>
                                    @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="9" class="text-center text-secondary py-5">
                                <i
                                    class="bi bi-people d-block fs-2 mb-2">
                                    </i>Belum ada data karyawan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $employees->links() }}</div>
    </div>
    @foreach ($employees as $employee)
        @if (!$employee->user)
            <div class="modal fade" id="accountModal-{{ $employee->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" method="POST" action="{{ route('employees.account', $employee) }}">@csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Akun — {{ $employee->display_name }}
                            </h5>
                            <button class="btn-close"
                                data-bs-dismiss="modal" type="button">
                                </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary small">Akun dibuat dengan role <strong>Karyawan</strong> dan terhubung
                                ke {{ $employee->kode_karyawan }}.</p>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input class="form-control"
                                    type="email" name="email" value="{{ old('email') }}" required>
                                    </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input class="form-control"
                                    type="password" name="password" minlength="8" required autocomplete="new-password">
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Konfirmasi Password</label>
                                <input
                                    class="form-control" type="password" name="password_confirmation" minlength="8"
                                    required autocomplete="new-password">
                                    </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-light border" data-bs-dismiss="modal"
                                type="button">Batal</button>
                                <button class="btn btn-success" type="submit">
                                    <i
                                    class="bi bi-person-plus me-2">
                                    </i>Buat Akun</button>
                                    </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
