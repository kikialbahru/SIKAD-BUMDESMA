@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('dashboard') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Dashboard</a>
    <h1 class="h3 mt-3 mb-1">Master Data</h1>
    <p class="text-secondary mb-0">Jadwal personal karyawan mengalahkan pengaturan umum saat menghitung keterlambatan dan rentang absen pulang. Jadwal yang masih dipakai karyawan tidak dapat dihapus; satu jadwal aktif harus selalu tersisa.</p>
</div>

@include('master-data._tabs')

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-clock me-2 text-primary">
                    </i>Daftar Jadwal Kerja</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jadwal</th>
                            <th>Jam</th>
                            <th>Toleransi</th>
                            <th>Hari Kerja</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $schedule)
                            <tr>
                                <td>
                                    <strong>{{ $schedule->nama }}</strong>
                                    <small class="d-block text-secondary">{{ $schedule->employees_count }} karyawan</small>
                                </td>
                                <td class="text-nowrap">{{ substr($schedule->jam_masuk, 0, 5) }} - {{ substr($schedule->jam_pulang, 0, 5) }}</td>
                                <td class="text-nowrap">{{ $schedule->toleransi_telat_menit }} mnt</td>
                                <td>
                                    @foreach((array) $schedule->hari_kerja as $day)
                                        <span class="badge text-bg-light border">{{ $days[$day] ?? $day }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($schedule->aktif)
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
                                        data-bs-target="#editSchedule-{{ $schedule->id }}"
                                        title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form class="d-inline" method="POST" action="{{ route('work-schedules.destroy', $schedule) }}" data-confirm="Hapus jadwal {{ $schedule->nama }}?">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus" @disabled($schedule->employees_count > 0)>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada jadwal kerja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Jadwal</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('work-schedules.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="sch_name">Nama jadwal</label>
                        <input class="form-control @error('nama') is-invalid @enderror" id="sch_name" name="nama" value="{{ old('nama') }}" required maxlength="100" placeholder="cth. Shift Pagi">@error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="sch_in">Jam masuk</label>
                            <input class="form-control @error('jam_masuk') is-invalid @enderror" id="sch_in" type="time" name="jam_masuk" value="{{ old('jam_masuk') }}" required>@error('jam_masuk')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="sch_out">Jam pulang</label>
                            <input class="form-control @error('jam_pulang') is-invalid @enderror" id="sch_out" type="time" name="jam_pulang" value="{{ old('jam_pulang') }}" required>@error('jam_pulang')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sch_tol">Toleransi telat (menit)</label>
                        <input class="form-control @error('toleransi_telat_menit') is-invalid @enderror" id="sch_tol" type="number" name="toleransi_telat_menit" value="{{ old('toleransi_telat_menit', 15) }}" min="0" max="180" required>@error('toleransi_telat_menit')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari kerja</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($days as $key => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="hari_kerja[]" value="{{ $key }}" id="sch_day_{{ $key }}" @checked(in_array($key, old('hari_kerja', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])))>
                                    <label class="form-check-label" for="sch_day_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('hari_kerja')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="sch_active" @checked(old('aktif', true))>
                        <label class="form-check-label" for="sch_active">Aktif (bisa dipilih saat tambah karyawan)</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-save me-2"></i>Simpan Jadwal</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($schedules as $schedule)
<div class="modal fade" id="editSchedule-{{ $schedule->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('work-schedules.update', $schedule) }}">@csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Jadwal Kerja</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama jadwal</label>
                    <input class="form-control" name="nama" value="{{ $schedule->nama }}" required maxlength="100">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Jam masuk</label>
                        <input class="form-control" type="time" name="jam_masuk" value="{{ substr($schedule->jam_masuk, 0, 5) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Jam pulang</label>
                        <input class="form-control" type="time" name="jam_pulang" value="{{ substr($schedule->jam_pulang, 0, 5) }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Toleransi telat (menit)</label>
                    <input class="form-control" type="number" name="toleransi_telat_menit" value="{{ $schedule->toleransi_telat_menit }}" min="0" max="180" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hari kerja</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($days as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hari_kerja[]" value="{{ $key }}" id="edit_day_{{ $schedule->id }}_{{ $key }}" @checked(in_array($key, (array) $schedule->hari_kerja))>
                                <label class="form-check-label" for="edit_day_{{ $schedule->id }}_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="aktif" value="1" id="edit_active_{{ $schedule->id }}" @checked($schedule->aktif)>
                    <label class="form-check-label" for="edit_active_{{ $schedule->id }}">Aktif</label>
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
