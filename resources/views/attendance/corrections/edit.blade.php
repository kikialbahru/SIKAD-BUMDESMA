@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('attendance.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Data Kehadiran</a>
    <h1 class="h3 mt-3 mb-1">Koreksi Absensi</h1>
    <p class="text-secondary mb-0">
        {{ $attendance->employee?->display_name }} ({{ $attendance->employee?->kode_karyawan }}) · {{ $attendance->tanggal->translatedFormat('l, d F Y') }}
    </p>
</div>

@if($attendance->foto_masuk || $attendance->foto_pulang)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill">
    </i>
    <span>Absensi ini memiliki bukti foto/GPS asli — bukti tersebut <strong>tidak diubah</strong> oleh koreksi ini. Mengubah jam akan menghapus persetujuan pulang awal bila ada.</span>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('attendance.corrections.update', $attendance) }}">
            @method('PUT')
            @include('attendance.corrections._form')
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-2">
                    </i>Simpan Koreksi</button>
                <a class="btn btn-light border" href="{{ route('attendance.index') }}">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
