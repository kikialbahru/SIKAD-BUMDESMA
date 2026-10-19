@extends('layouts.app')

@section('content')
<div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
    <a class="text-decoration-none me-auto" href="{{ route('attendance.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Data Kehadiran</a>
    @if($attendance->waktu_masuk && ! $attendance->foto_masuk)
        <span class="badge text-bg-info" title="Dicatat manual oleh admin tanpa foto/GPS">Input manual</span>
    @endif
    @if(auth()->user()->isAdmin())
        <a class="btn btn-sm btn-outline-warning" href="{{ route('attendance.corrections.edit', $attendance) }}">
            <i class="bi bi-pencil me-1">
            </i>Koreksi</a>
    @endif
    <span class="badge text-bg-{{ $attendance->status === 'late' ? 'warning' : ($attendance->status === 'present' ? 'success' : 'secondary') }} fs-6">
        {{ ['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'permission' => 'Izin', 'sick' => 'Sakit', 'duty' => 'Dinas Keluar', 'absent' => 'Alpa'][$attendance->status] ?? ucfirst($attendance->status) }}
    </span>
</div>

@if($attendance->catatan)
<div class="alert alert-info d-flex align-items-start gap-2">
    <i class="bi bi-info-circle-fill mt-1">
    </i>
    <div>
        <strong>Keterangan:</strong> {{ $attendance->catatan }}
    </div>
</div>
@endif

@php($anomalies = $attendance->anomalyFlags())
@if($anomalies && auth()->user()->isAdmin())
<div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1">
    </i>
    <div>
        <strong>Perlu perhatian:</strong>
        <ul class="mb-0 ps-3">
            @foreach($anomalies as $flag)
                <li>{{ $flag }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
    @if($attendance->employee?->foto)
        <img
            src="{{ asset('storage/'.$attendance->employee->foto) }}"
            class="rounded-circle"
            width="64"
            height="64"
            style="object-fit:cover"
            alt="Foto profil">
    @else
    <span class="employee-avatar" style="width:64px;height:64px;font-size:1.5rem">
        {{ strtoupper(substr($attendance->employee?->display_name ?? '-', 0, 1)) }}
    </span>
    @endif
    <div class="me-auto">
        <h1 class="h5 mb-0">
            {{ $attendance->employee?->display_name ?? '-' }}
        </h1>
        <p class="text-secondary small mb-0">
            {{ $attendance->employee?->kode_karyawan }} · {{ $attendance->employee?->position?->nama ?? 'Tanpa jabatan' }}
        </p>
    </div>
    <span class="badge text-bg-light border px-3 py-2">
        {{ $attendance->tanggal->translatedFormat('l, d F Y') }}
    </span>
</div>
</div>

<div class="row g-4">
    @foreach([['masuk', 'Absen Masuk', 'bi-box-arrow-in-right', 'success'], ['pulang', 'Absen Pulang', 'bi-box-arrow-right', 'primary']] as [$kind, $label, $icon, $color])
    @php($at = $kind === 'masuk' ? $attendance->waktu_masuk : $attendance->waktu_pulang)
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">
                <i class="bi {{ $icon }} me-2 text-{{ $color }}">
                </i>
                {{ $label }}
            </h2>
        </div>
        <div class="card-body">
            @php($photo = $kind === 'masuk' ? $attendance->foto_masuk : $attendance->foto_pulang)
            @if($photo)
                <a href="{{ asset('storage/'.$photo) }}" target="_blank">
                    <img
                        src="{{ asset('storage/'.$photo) }}"
                        class="img-fluid rounded-3 border w-100 mb-3"
                        style="max-height:320px;object-fit:cover"
                        alt="Foto {{ $label }}">
                </a>
            @else
            <div class="alert alert-secondary">Belum ada foto {{ strtolower($label) }}.</div>
            @endif
            <dl class="row small mb-0">
                <dt class="col-4 text-secondary">Waktu</dt>
                <dd class="col-8 fw-semibold">
                    {{ $at?->format('H:i:s').' WIB' ?? '-' }} <span class="text-secondary fw-normal">(server)
                    </span>
                </dd>
                <dt class="col-4 text-secondary">Lokasi</dt>
                <dd class="col-8">
                    {{ $office?->nama ?? 'Lokasi kantor belum diatur' }}
                </dd>
                <dt class="col-4 text-secondary">Koordinat</dt>
                <dd class="col-8">
                    {{ ($kind === 'masuk' ? $attendance->lintang_masuk : $attendance->lintang_pulang) !== null ? number_format($kind === 'masuk' ? $attendance->lintang_masuk : $attendance->lintang_pulang, 6).', '.number_format($kind === 'masuk' ? $attendance->bujur_masuk : $attendance->bujur_pulang, 6) : '-' }}
                </dd>
                <dt class="col-4 text-secondary">Akurasi GPS</dt>
                <dd class="col-8">
                    {{ ($kind === 'masuk' ? $attendance->akurasi_masuk : $attendance->akurasi_pulang) !== null ? '±'.($kind === 'masuk' ? $attendance->akurasi_masuk : $attendance->akurasi_pulang).' meter' : '-' }}
                </dd>
                <dt class="col-4 text-secondary">Jarak dari kantor</dt>
                <dd class="col-8">
                    {{ ($kind === 'masuk' ? $attendance->jarak_masuk_meter : $attendance->jarak_pulang_meter) !== null ? '±'.($kind === 'masuk' ? $attendance->jarak_masuk_meter : $attendance->jarak_pulang_meter).' meter' : '-' }}
                </dd>
                <dt class="col-4 text-secondary">Status lokasi</dt>
                <dd class="col-8">
                    @php($valid = $kind === 'masuk' ? $attendance->lokasi_masuk_valid : $attendance->lokasi_pulang_valid)
                    @if($at === null)<span class="text-secondary">-</span>
                    @elseif($valid === true)
                    <span class="badge text-bg-success">
                        <i class="bi bi-geo-alt me-1">
                        </i>Lokasi Sesuai</span>
                    @elseif($valid === false)
                    <span class="badge text-bg-danger">
                        <i class="bi bi-geo-alt me-1">
                        </i>Di Luar Area</span>
                    @else<span class="badge text-bg-secondary">Tercatat tanpa batas radius</span>@endif
                </dd>
            </dl>
        </div>
    </div>
    </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0">
            <i class="bi bi-map me-2 text-primary">
            </i>Peta Lokasi</h2>
    </div>
    <div class="card-body">
        @if(($attendance->lintang_masuk && $attendance->bujur_masuk) || ($attendance->lintang_pulang && $attendance->bujur_pulang))
        <div id="attendanceMap" style="height:380px;border-radius:.9rem;z-index:0">
        </div>
        <div class="d-flex flex-wrap gap-3 mt-3 small text-secondary">
            <span>🏢 {{ $office?->nama ?? 'Kantor (belum diatur)' }}</span>
            @if($attendance->lintang_masuk)
                <span>🟢 Absen masuk (±{{ $attendance->jarak_masuk_meter ?? '?' }} m)
                </span>
            @endif
            @if($attendance->lintang_pulang)
                <span>🔵 Absen pulang (±{{ $attendance->jarak_pulang_meter ?? '?' }} m)
                </span>
            @endif
        </div>
        @else
        <div
            class="alert alert-secondary mb-0">Belum
            ada
            koordinat
            GPS
            pada
            absensi
            ini
            (data lama sebelum fitur lokasi).</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if(($attendance->lintang_masuk && $attendance->bujur_masuk) || ($attendance->lintang_pulang && $attendance->bujur_pulang))
@php($mapOffice = $office ? ['lat' => (float) $office->lintang, 'lng' => (float) $office->bujur, 'name' => $office->nama, 'radius' => (int) $office->radius_meter] : null)
@php($mapIn = $attendance->lintang_masuk ? ['lat' => (float) $attendance->lintang_masuk, 'lng' => (float) $attendance->bujur_masuk] : null)
@php($mapOut = $attendance->lintang_pulang ? ['lat' => (float) $attendance->lintang_pulang, 'lng' => (float) $attendance->bujur_pulang] : null)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const office = @json($mapOffice);
    const checkIn = @json($mapIn);
    const checkOut = @json($mapOut);
    const points = [office, checkIn, checkOut].filter(Boolean);
    const map = L.map('attendanceMap').setView([points[0].lat, points[0].lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const bounds = [];
    if (office) {
        L.marker([office.lat, office.lng]).addTo(map).bindPopup('🏢 ' + office.name);
        L.circle([office.lat, office.lng], { radius: office.radius, color: '#106fae', fillOpacity: 0.08 }).addTo(map);
        bounds.push([office.lat, office.lng]);
    }
    if (checkIn) {
        const m = L.circleMarker([checkIn.lat, checkIn.lng], { radius: 8, color: '#198754', fillColor: '#198754', fillOpacity: 0.9 }).addTo(map).bindPopup('🟢 Absen masuk');
        bounds.push([checkIn.lat, checkIn.lng]);
        if (office) L.polyline([[office.lat, office.lng], [checkIn.lat, checkIn.lng]], { dashArray: '6 6' }).addTo(map);
    }
    if (checkOut) {
        L.circleMarker([checkOut.lat, checkOut.lng], { radius: 8, color: '#0d6efd', fillColor: '#0d6efd', fillOpacity: 0.9 }).addTo(map).bindPopup('🔵 Absen pulang');
        bounds.push([checkOut.lat, checkOut.lng]);
        if (office) L.polyline([[office.lat, office.lng], [checkOut.lat, checkOut.lng]], { dashArray: '6 6', color: '#0d6efd' }).addTo(map);
    }
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] });
})();
</script>
@endif
@endpush
