@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Absen Kunjungan</h1>
            <p class="text-secondary mb-0">Catat kunjungan lapangan dengan judul dan foto. Maksimal {{ $maxPerDay }} kunjungan per hari per karyawan.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-1">Rekap {{ $monthNav['label'] }}</h2>
                    @unless(auth()->user()->isAdmin())
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-secondary">Hari ini</span>
                            <strong>{{ $todayCount }} kunjungan</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-secondary">Bulan {{ $monthNav['label'] }}</span>
                            <strong>{{ $monthCount }} kunjungan</strong>
                        </div>
                        <p class="text-secondary small mb-0 mt-2">Batas maksimal {{ $maxPerDay }} kunjungan per hari.</p>
                    @else
                        <p class="text-secondary small mb-3">Ringkasan jumlah kunjungan tiap karyawan pada bulan ini.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Karyawan</th>
                                        <th class="text-center">Hari ini</th>
                                        <th class="text-center">Bulan ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summary as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row['employee']->user?->name ?? '-' }}</strong>
                                                <small class="d-block text-secondary">{{ $row['employee']->employee_code }}</small>
                                            </td>
                                            <td class="text-center">{{ $row['today'] }} kunjungan</td>
                                            <td class="text-center">{{ $row['month'] }} kunjungan</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-secondary py-3">Belum ada karyawan aktif.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            @unless(auth()->user()->isAdmin())
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">Catat kunjungan baru</h2>
                        @if($todayCount >= $maxPerDay)
                            <div class="alert alert-warning mb-0">Batas {{ $maxPerDay }} kunjungan hari ini sudah tercapai. Anda dapat mencatat lagi besok.</div>
                        @else
                            <form method="POST" action="{{ route('visits.store') }}" enctype="multipart/form-data" class="row g-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label small" for="visit-title">Judul kunjungan</label>
                                    <input class="form-control @error('judul') is-invalid @enderror" id="visit-title" name="judul" value="{{ old('judul') }}" maxlength="255" placeholder="cth. Kunjungan ke kelompok tani Desa Tarub" required>
                                    @error('judul')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small" for="visit-description">Deskripsi <span class="text-secondary">(opsional)</span></label>
                                    <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="visit-description" name="deskripsi" rows="2" maxlength="2000" placeholder="cth. Bertemu ketua kelompok tani, membahas bantuan bibit">{{ old('deskripsi') }}</textarea>
                                    @error('deskripsi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="visit-date">Tanggal kunjungan</label>
                                    <input class="form-control @error('tanggal') is-invalid @enderror" id="visit-date" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                    @error('tanggal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="visit-photo">Foto kunjungan</label>
                                    <input class="form-control @error('foto') is-invalid @enderror" id="visit-photo" name="foto" type="file" accept="image/jpeg,image/png,image/webp" required>
                                    @error('foto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">JPG/PNG/WebP, maks 2 MB.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Lokasi kunjungan (GPS)</label>
                                    <div class="p-3 rounded-3 bg-light">
                                        <div class="small" data-role="gps-status">
                                            <span class="text-secondary">Lokasi belum diambil. Aktifkan GPS lalu tekan tombol di bawah.</span>
                                        </div>
                                        <div class="text-secondary small mt-1" data-role="gps-detail"></div>
                                        <div id="visitMapPreview" class="rounded border mt-2 d-none" style="height:220px;z-index:0"></div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-role="btn-gps">
                                            <i class="bi bi-crosshair me-1"></i>Ambil Lokasi Saat Ini
                                        </button>
                                    </div>
                                    <input type="hidden" name="lintang" data-role="input-lat" value="{{ old('lintang') }}">
                                    <input type="hidden" name="bujur" data-role="input-lng" value="{{ old('bujur') }}">
                                    <input type="hidden" name="akurasi" data-role="input-acc" value="{{ old('akurasi') }}">
                                    @error('lintang')
                                        <div class="text-danger small mt-1">Lokasi GPS wajib diambil sebelum menyimpan.</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-geo-alt me-2"></i>Simpan kunjungan
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">Filter data</h2>
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Bulan</label>
                                <input class="form-control" name="month" type="month" value="{{ $filters['month'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Karyawan</label>
                                <select class="form-select" name="karyawan_id">
                                    <option value="">Semua karyawan</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) ($filters['karyawan_id'] ?? '') === (string) $employee->id)>
                                            {{ $employee->kode_karyawan }} — {{ $employee->user?->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Cari judul</label>
                                <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul kunjungan">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                <a class="btn btn-light border" href="{{ route('visits.index') }}"><i class="bi bi-arrow-counterclockwise"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>

    @unless(auth()->user()->isAdmin())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-6 col-lg-3">
                        <label class="form-label small">Bulan</label>
                        <input class="form-control" name="month" type="month" value="{{ $filters['month'] }}">
                    </div>
                    <div class="col-6 col-lg-4">
                        <label class="form-label small">Cari judul</label>
                        <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul kunjungan">
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                        <a class="btn btn-light border" href="{{ route('visits.index') }}"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a class="btn btn-light border btn-sm" href="{{ route('visits.index', array_merge(request()->except(['page']), ['month' => $monthNav['prev']])) }}">
            <i class="bi bi-chevron-left"></i> {{ $monthNav['prevLabel'] }}
        </a>
        <span class="badge text-bg-light border px-3 py-2">{{ $monthNav['label'] }}</span>
        @if (!$monthNav['isCurrent'])
            <a class="btn btn-light border btn-sm" href="{{ route('visits.index', array_merge(request()->except(['page']), ['month' => $monthNav['next']])) }}">
                {{ $monthNav['nextLabel'] }} <i class="bi bi-chevron-right"></i>
            </a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('visits.index') }}">Bulan ini</a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0"><i class="bi bi-map me-2 text-primary"></i>Peta Sebaran{{ auth()->user()->isAdmin() ? ' (semua karyawan)' : ' (kunjungan saya)' }}</h2>
        </div>
        <div class="card-body">
            @if($mapPoints->isNotEmpty())
                <div id="visitsMap" style="height:380px;border-radius:.9rem;z-index:0"></div>
                <p class="text-secondary small mb-0 mt-2">Menampilkan {{ $mapPoints->count() }} titik kunjungan berkoordinat pada filter bulan ini. Klik pin untuk detail.</p>
            @elseif(auth()->user()->isAdmin() && empty($filters['karyawan_id']))
                <div class="alert alert-info mb-0">Lokasi kunjungan bersifat per karyawan — pilih satu karyawan pada filter untuk melihat peta sebarannya.</div>
            @else
                <div class="alert alert-secondary mb-0">Belum ada titik lokasi pada filter ini.</div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if(auth()->user()->isAdmin())
                            <th>Karyawan</th>
                        @endif
                        <th>Judul kunjungan</th>
                        <th>Tanggal</th>
                        <th>Foto</th>
                        <th>Lokasi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        <tr>
                            @if(auth()->user()->isAdmin())
                                <td>
                                    <strong>{{ $visit->employee?->user?->name ?? '-' }}</strong>
                                    <small class="d-block text-secondary">{{ $visit->employee?->kode_karyawan }}</small>
                                </td>
                            @endif
                            <td>
                                <strong>{{ $visit->judul }}</strong>
                                @if($visit->deskripsi)
                                    <small class="d-block text-secondary">{{ \Illuminate\Support\Str::limit($visit->deskripsi, 80) }}</small>
                                @endif
                            </td>
                            <td>{{ $visit->tanggal->translatedFormat('d M Y') }}</td>
                            <td>
                                <a href="{{ asset('storage/' . $visit->foto) }}" target="_blank" title="Lihat foto">
                                    <img src="{{ asset('storage/' . $visit->foto) }}" width="48" height="48" class="rounded border" style="object-fit:cover" alt="Foto kunjungan">
                                </a>
                            </td>
                            <td>
                                @if($showLocation && $visit->lintang && $visit->bujur)
                                    <a class="text-decoration-none small" href="{{ route('visits.show', $visit) }}" title="Lihat di peta">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ number_format($visit->lintang, 5) }}, {{ number_format($visit->bujur, 5) }}
                                    </a>
                                @else
                                    <span class="text-secondary small">-</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('visits.show', $visit) }}" title="Detail + peta">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit) }}" data-confirm="Hapus data kunjungan ini? Foto ikut terhapus permanen.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="text-center text-secondary py-5">
                                <i class="bi bi-geo-alt d-block fs-2 mb-2"></i>Belum ada data kunjungan pada bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $visits->links() }}</div>
    </div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@if($mapPoints->isNotEmpty())
<script>
(function () {
    const points = @json($mapPoints);
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const map = L.map('visitsMap').setView([points[0].lat, points[0].lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const bounds = [];
    points.forEach((p) => {
        L.marker([p.lat, p.lng]).addTo(map)
            .bindPopup('<strong>' + esc(p.title) + '</strong><br><small>' + esc(p.employee) + ' · ' + esc(p.date) + '</small><br><a href="' + esc(p.url) + '">Lihat detail</a>');
        bounds.push([p.lat, p.lng]);
    });
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] });
})();
</script>
@endif
@unless(auth()->user()->isAdmin())
<script>
(function () {
    const form = document.querySelector('form[action="{{ route('visits.store') }}"]');
    if (!form) return;
    const statusEl = form.querySelector('[data-role="gps-status"]');
    const detailEl = form.querySelector('[data-role="gps-detail"]');
    const latInput = form.querySelector('[data-role="input-lat"]');
    const lngInput = form.querySelector('[data-role="input-lng"]');
    const accInput = form.querySelector('[data-role="input-acc"]');
    const btn = form.querySelector('[data-role="btn-gps"]');
    const mapBox = document.getElementById('visitMapPreview');
    let map = null, marker = null;

    function showPreview(lat, lng) {
        mapBox.classList.remove('d-none');
        if (!map) {
            map = L.map('visitMapPreview').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        } else {
            map.setView([lat, lng], 16);
        }
        if (marker) marker.remove();
        marker = L.marker([lat, lng]).addTo(map).bindPopup('Lokasi kunjungan').openPopup();
        setTimeout(() => map.invalidateSize(), 200);
    }

    function fetchLocation(onDone) {
        if (!navigator.geolocation) {
            statusEl.innerHTML = '<span class="text-danger">Perangkat tidak mendukung GPS.</span>';
            return;
        }
        statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><span class="text-secondary">Mengambil lokasi...</span>';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude, lng = pos.coords.longitude, acc = pos.coords.accuracy;
                latInput.value = lat; lngInput.value = lng; accInput.value = Math.round(acc);
                statusEl.innerHTML = '<span class="badge text-bg-success"><i class="bi bi-geo-alt me-1"></i>Lokasi terkunci</span>';
                detailEl.textContent = Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6) + ' (±' + Math.round(acc) + ' m)';
                showPreview(lat, lng);
                if (onDone) onDone();
            },
            () => { statusEl.innerHTML = '<span class="text-danger">Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan lalu coba lagi.</span>'; },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    btn.addEventListener('click', () => fetchLocation());
    form.addEventListener('submit', (e) => {
        if (!latInput.value || !lngInput.value) {
            e.preventDefault();
            fetchLocation(() => form.submit());
        }
    });
})();
</script>
@endunless
@endpush
