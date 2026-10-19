@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('attendance.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Data Kehadiran</a>
    <h1 class="h3 mt-3 mb-1">Pengaturan Absensi</h1>
    <p class="text-secondary mb-0">Atur jam kerja, toleransi keterlambatan, dan rentang absensi pulang.</p>
    <a class="btn btn-outline-success mt-3" href="{{ route('attendance.locations.index') }}">
        <i class="bi bi-geo-alt me-2">
        </i>Pengaturan Lokasi Absensi (titik kantor, radius &amp; akurasi GPS)
    </a>
</div>

<form method="POST" action="{{ route('attendance.settings.update') }}" class="row g-4">
    @csrf
    @method('PUT')
    <div class="col-lg-6 order-1">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-clock me-2 text-primary">
                    </i>Jam Kerja</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="jam_masuk">Jam Masuk</label>
                        <input
                            class="form-control @error('jam_masuk') is-invalid @enderror"
                            id="jam_masuk"
                            type="time"
                            name="jam_masuk"
                            value="{{ old('jam_masuk', substr((string) $settings->jam_masuk, 0, 5)) }}"
                            required>
                        @error('jam_masuk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="jam_pulang">Jam Pulang</label>
                        <input
                            class="form-control @error('jam_pulang') is-invalid @enderror"
                            id="jam_pulang"
                            type="time"
                            name="jam_pulang"
                            value="{{ old('jam_pulang', substr((string) $settings->jam_pulang, 0, 5)) }}"
                            required>
                        @error('jam_pulang')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="mulai_absen_masuk">Absen Masuk Mulai</label>
                        <input
                            class="form-control @error('mulai_absen_masuk') is-invalid @enderror"
                            id="mulai_absen_masuk"
                            type="time"
                            name="mulai_absen_masuk"
                            value="{{ old('mulai_absen_masuk', substr((string) ($settings->mulai_absen_masuk ?? '07:00'), 0, 5)) }}"
                            required>
                        @error('mulai_absen_masuk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div
                    class="form-text mt-2">Karyawan
                    dengan
                    jadwal
                    kerja
                    khusus
                    mengikuti
                    jam
                    pada
                    jadwalnya
                    masing-masing.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-alarm me-2 text-primary">
                    </i>Keterlambatan</h2>
            </div>
            <div class="card-body">
                <label class="form-label" for="toleransi_telat_menit">Batas Toleransi (menit)</label>
                <input
                    class="form-control @error('toleransi_telat_menit') is-invalid @enderror"
                    id="toleransi_telat_menit"
                    type="number"
                    name="toleransi_telat_menit"
                    min="0"
                    max="180"
                    value="{{ old('toleransi_telat_menit', $settings->toleransi_telat_menit) }}"
                    required>
                @error('toleransi_telat_menit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div
                    class="form-text mt-2">Absen
                    setelah
                    jam
                    masuk
                    +
                    toleransi
                    tercatat
                    Terlambat
                    (mis.
                    jam
                    08:00
                    +
                    10
                    menit
                    =
                    telat
                    mulai
                    08:11);
                    dalam
                    batas
                    toleransi
                    tercatat
                    Hadir
                    dengan
                    0
                    menit.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-box-arrow-right me-2 text-primary">
                    </i>Absensi Pulang</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="mulai_absen_pulang">Mulai Absen Pulang</label>
                        <input
                            class="form-control @error('mulai_absen_pulang') is-invalid @enderror"
                            id="mulai_absen_pulang"
                            type="time"
                            name="mulai_absen_pulang"
                            value="{{ old('mulai_absen_pulang', substr((string) $settings->mulai_absen_pulang, 0, 5)) }}"
                            required>
                        @error('mulai_absen_pulang')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="akhir_absen_pulang">Akhir Absen Pulang</label>
                        <input
                            class="form-control @error('akhir_absen_pulang') is-invalid @enderror"
                            id="akhir_absen_pulang"
                            type="time"
                            name="akhir_absen_pulang"
                            value="{{ old('akhir_absen_pulang', substr((string) $settings->akhir_absen_pulang, 0, 5)) }}"
                            required>
                        @error('akhir_absen_pulang')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-lightning me-2 text-primary">
                    </i>Poin Keterlambatan Otomatis</h2>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="poin_otomatis_aktif"
                        name="poin_otomatis_aktif"
                        value="1"
                        @checked(old('poin_otomatis_aktif', $settings->poin_otomatis_aktif))>
                    <label class="form-check-label" for="poin_otomatis_aktif">Aktifkan generate poin otomatis</label>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="blok_menit_poin">Setiap (menit)</label>
                        <input
                            class="form-control @error('blok_menit_poin') is-invalid @enderror"
                            id="blok_menit_poin"
                            type="number"
                            name="blok_menit_poin"
                            min="1"
                            max="240"
                            value="{{ old('blok_menit_poin', $settings->blok_menit_poin) }}"
                            required>
                        @error('blok_menit_poin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="poin_per_blok">Poin per blok</label>
                        <input
                            class="form-control @error('poin_per_blok') is-invalid @enderror"
                            id="poin_per_blok"
                            type="number"
                            name="poin_per_blok"
                            min="0"
                            max="100"
                            value="{{ old('poin_per_blok', $settings->poin_per_blok) }}"
                            required>
                        @error('poin_per_blok')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div
                    class="form-text mt-2">Contoh:
                    setiap
                    15
                    menit
                    =
                    1
                    poin.
                    Bagian
                    dari
                    blok
                    dihitung
                    penuh.
                    Poin
                    hanya
                    berupa
                    catatan
                    (tidak memotong gaji).</div>
            </div>
        </div>
    </div>
    <div class="col-12 order-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap gap-2 justify-content-end">
                <a class="btn btn-light border" href="{{ route('attendance.index') }}">Batal</a>
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bi bi-save me-2">
                    </i>Simpan Pengaturan</button>
            </div>
        </div>
    </div>
</form>
@endsection
