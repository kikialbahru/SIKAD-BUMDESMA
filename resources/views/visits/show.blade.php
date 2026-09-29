@extends('layouts.app')

@section('content')
    <div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
        <a class="text-decoration-none me-auto" href="{{ route('visits.index') }}">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Absen Kunjungan</a>
        @if(auth()->user()->isAdmin() || $visit->karyawan_id === auth()->user()->employee?->id)
            <button
                class="btn btn-sm btn-outline-primary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#editVisitModal">
                <i class="bi bi-pencil me-1"></i>Ubah</button>
            <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit) }}" data-confirm="Hapus data kunjungan ini? Foto ikut terhapus permanen.">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="bi bi-trash me-1"></i>Hapus</button>
            </form>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Detail Kunjungan</h2>
                </div>
                <div class="card-body">
                    <a href="{{ asset('storage/' . $visit->foto) }}" target="_blank">
                        <img src="{{ asset('storage/' . $visit->foto) }}" class="img-fluid rounded-3 border w-100 mb-3" style="max-height:320px;object-fit:cover" alt="Foto kunjungan">
                    </a>
                    <dl class="row small mb-0">
                        <dt class="col-4 text-secondary">Judul</dt>
                        <dd class="col-8 fw-semibold">{{ $visit->judul }}</dd>
                        @if($visit->deskripsi)
                        <dt class="col-4 text-secondary">Deskripsi</dt>
                        <dd class="col-8">{{ $visit->deskripsi }}</dd>
                        @endif
                        <dt class="col-4 text-secondary">Tanggal</dt>
                        <dd class="col-8">{{ $visit->tanggal->translatedFormat('l, d F Y') }}</dd>
                        <dt class="col-4 text-secondary">Karyawan</dt>
                        <dd class="col-8">{{ $visit->employee?->user?->name ?? '-' }} <span class="text-secondary">({{ $visit->employee?->kode_karyawan }})</span></dd>
                        <dt class="col-4 text-secondary">Koordinat</dt>
                        <dd class="col-8">
                            @if($visit->lintang && $visit->bujur)
                                {{ number_format($visit->lintang, 6) }}, {{ number_format($visit->bujur, 6) }}
                                @if($visit->akurasi !== null)
                                    <span class="text-secondary">(±{{ $visit->akurasi }} m)</span>
                                @endif
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0"><i class="bi bi-map me-2 text-primary"></i>Peta Lokasi</h2>
                </div>
                <div class="card-body">
                    @if($visit->lintang && $visit->bujur)
                        <div id="visitMap" style="height:420px;border-radius:.9rem;z-index:0"></div>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $visit->lintang }}&mlon={{ $visit->bujur }}#map=17/{{ $visit->lintang }}/{{ $visit->bujur }}">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka di OpenStreetMap</a>
                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.google.com/maps?q={{ $visit->lintang }},{{ $visit->bujur }}">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Google Maps</a>
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">Kunjungan ini belum memiliki koordinat GPS (data lama sebelum fitur lokasi).</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@if(auth()->user()->isAdmin() || $visit->karyawan_id === auth()->user()->employee?->id)
    <div class="modal fade" id="editVisitModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('visits.update', $visit) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kunjungan</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input class="form-control" name="judul" value="{{ $visit->judul }}" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi (opsional)</label>
                        <textarea class="form-control" name="deskripsi" rows="3" maxlength="2000">{{ $visit->deskripsi }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input class="form-control" type="date" name="tanggal" value="{{ $visit->tanggal->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required>
                        <div class="form-text">Maksimal hari ini. Lokasi GPS tidak dapat diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti foto (opsional)</label>
                        <input class="form-control" type="file" name="foto" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light border" data-bs-dismiss="modal" type="button">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($visit->lintang && $visit->bujur)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const lat = {{ (float) $visit->lintang }}, lng = {{ (float) $visit->bujur }};
    const rawTitle = @json($visit->judul);
    const title = document.createElement('div');
    title.textContent = rawTitle;
    const map = L.map('visitMap').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(title).openPopup();
})();
</script>
@endif
@endpush
