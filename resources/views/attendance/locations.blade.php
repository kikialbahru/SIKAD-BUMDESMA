@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('attendance.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Data Kehadiran</a>
    <h1 class="h3 mt-3 mb-1">Lokasi Absensi</h1>
    <p
        class="text-secondary mb-0">Tentukan
        titik
        kantor
        dan
        radius
        absensi.
        Karyawan
        wajib
        selfie
        +
        GPS
        dan
        hanya
        bisa
        absen
        di
        dalam
        radius
        (validasi di server).</p>
</div>

@if(! $active)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill">
    </i>
    <span>Belum ada lokasi aktif — validasi radius <strong>nonaktif</strong> dan lokasi karyawan hanya dicatat. Tambahkan lokasi di bawah lalu aktifkan.</span>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-geo-alt me-2 text-primary">
                    </i>Daftar Lokasi</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Koordinat</th>
                        <th>Radius / Akurasi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($locations as $location)
                    <tr>
                        <td>
                            <strong>
                                {{ $location->nama }}
                            </strong>@if($location->alamat)
                                <small class="d-block text-secondary">
                                    {{ $location->alamat }}
                                </small>
                            @endif @unless($location->wajib_radius)
                                <span class="badge text-bg-warning">Radius masuk tidak ditegakkan</span>
                            @endunless @unless($location->wajib_radius_pulang)
                                <span class="badge text-bg-info">Pulang boleh dari lapangan</span>
                            @endunless
                        </td>
                        <td class="small">
                            {{ number_format($location->lintang, 6) }}, {{ number_format($location->bujur, 6) }}
                        </td>
                        <td class="small">
                            {{ $location->radius_meter }} m / ±{{ $location->maks_akurasi_meter }} m</td>
                        <td>@if($location->aktif)
                                <span class="badge text-bg-success">Aktif</span>
                            @else
                            <span class="badge text-bg-secondary">Nonaktif</span>
                        @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @unless($location->aktif)
                            <form class="d-inline" method="POST" action="{{ route('attendance.locations.activate', $location) }}">@csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success" type="submit" title="Aktifkan">
                                    <i class="bi bi-check-circle">
                                    </i>
                                </button>
                            </form>
                            @endunless
                            <button
                                class="btn btn-sm btn-outline-primary"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#editLocation-{{ $location->id }}"
                                title="Ubah">
                            <i class="bi bi-pencil">
                            </i>
                            </button>
                            <form class="d-inline" method="POST" action="{{ route('attendance.locations.destroy', $location) }}" data-confirm="Hapus lokasi {{ $location->nama }}?">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                    <i class="bi bi-trash">
                                    </i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">
                            <i class="bi bi-geo-alt d-block fs-2 mb-2">
                            </i>Belum ada lokasi absensi.</td>
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
                    </i>Tambah Lokasi</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('attendance.locations.store') }}" id="locationForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="loc_name">Nama Lokasi</label>
                        <input
                            class="form-control"
                            id="loc_name"
                            name="nama"
                            value="{{ old('nama', 'Kantor BUMDESMA LKD TARUB') }}"
                            required
                            maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="loc_address">Alamat</label>
                        <input
                            class="form-control"
                            id="loc_address"
                            name="alamat"
                            value="{{ old('alamat') }}"
                            maxlength="255"
                            placeholder="cth. Jl. Raya Tarub, Tegal">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="loc_lat">Latitude</label>
                            <input
                                class="form-control"
                                id="loc_lat"
                                name="lintang"
                                value="{{ old('lintang') }}"
                                required
                                inputmode="decimal"
                                placeholder="-6.xxxxxx">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="loc_lng">Longitude</label>
                            <input
                                class="form-control"
                                id="loc_lng"
                                name="bujur"
                                value="{{ old('bujur') }}"
                                required
                                inputmode="decimal"
                                placeholder="109.xxxxxx">
                        </div>
                    </div>
                    <button class="btn btn-outline-primary w-100 mb-3" type="button" id="useCurrentLocation">
                        <i class="bi bi-crosshair me-2">
                        </i>Gunakan Lokasi Saat Ini</button>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="loc_radius">Radius (meter)
                            </label>
                            <input
                                class="form-control"
                                id="loc_radius"
                                type="number"
                                name="radius_meter"
                                value="{{ old('radius_meter', 100) }}"
                                min="10"
                                max="5000"
                                required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="loc_acc">Batas Akurasi GPS (meter)
                            </label>
                            <input
                                class="form-control"
                                id="loc_acc"
                                type="number"
                                name="maks_akurasi_meter"
                                value="{{ old('maks_akurasi_meter', 100) }}"
                                min="10"
                                max="1000"
                                required>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="loc_enforce"
                            name="wajib_radius"
                            value="1"
                            checked>
                        <label class="form-check-label" for="loc_enforce">Tegakkan batas radius <strong>absen masuk</strong> (tolak absen di luar area)
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="loc_enforce_out"
                            name="wajib_radius_pulang"
                            value="1">
                        <label class="form-check-label" for="loc_enforce_out">Tegakkan batas radius <strong>absen pulang</strong> (matikan jika karyawan boleh pulang dari lapangan)
                        </label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-save me-2">
                        </i>Simpan &amp; Aktifkan Lokasi</button>
                    <div class="form-text mt-2">Lokasi baru otomatis menjadi lokasi aktif.</div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($locations as $location)
<div class="modal fade" id="editLocation-{{ $location->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('attendance.locations.update', $location) }}">@csrf @method('PUT')
        <div class="modal-header">
            <h5 class="modal-title">Ubah Lokasi — {{ $location->nama }}
            </h5>
            <button class="btn-close" type="button" data-bs-dismiss="modal">
            </button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nama Lokasi</label>
                <input class="form-control" name="nama" value="{{ $location->nama }}" required maxlength="100">
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <input class="form-control" name="alamat" value="{{ $location->alamat }}" maxlength="255">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Latitude</label>
                    <input
                        class="form-control"
                        name="lintang"
                        value="{{ $location->lintang }}"
                        required
                        inputmode="decimal">
                </div>
                <div class="col-6">
                    <label class="form-label">Longitude</label>
                    <input
                        class="form-control"
                        name="bujur"
                        value="{{ $location->bujur }}"
                        required
                        inputmode="decimal">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Radius (meter)
                    </label>
                    <input
                        class="form-control"
                        type="number"
                        name="radius_meter"
                        value="{{ $location->radius_meter }}"
                        min="10"
                        max="5000"
                        required>
                </div>
                <div class="col-6">
                    <label class="form-label">Batas Akurasi (meter)
                    </label>
                    <input
                        class="form-control"
                        type="number"
                        name="maks_akurasi_meter"
                        value="{{ $location->maks_akurasi_meter }}"
                        min="10"
                        max="1000"
                        required>
                </div>
            </div>
            <div class="form-check form-switch">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="wajib_radius"
                    value="1"
                    @checked($location->wajib_radius)>
                <label class="form-check-label">Tegakkan batas radius absen masuk</label>
            </div>
            <div class="form-check form-switch mt-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="wajib_radius_pulang"
                    value="1"
                    @checked($location->wajib_radius_pulang)>
                <label class="form-check-label">Tegakkan batas radius absen pulang</label>
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

@push('scripts')
<script>
document.getElementById('useCurrentLocation')?.addEventListener('click', function () {
    const btn = this;
    if (!navigator.geolocation) { alert('Perangkat tidak mendukung geolokasi.'); return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mencari lokasi...';
    navigator.geolocation.getCurrentPosition(function (pos) {
        document.getElementById('loc_lat').value = pos.coords.latitude.toFixed(6);
        document.getElementById('loc_lng').value = pos.coords.longitude.toFixed(6);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-crosshair me-2"></i>Gunakan Lokasi Saat Ini';
    }, function (err) {
        alert('Lokasi tidak dapat ditemukan: ' + (err.message || 'pastikan GPS aktif.'));
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-crosshair me-2"></i>Gunakan Lokasi Saat Ini';
    }, { enableHighAccuracy: true, timeout: 15000 });
});
</script>
@endpush
