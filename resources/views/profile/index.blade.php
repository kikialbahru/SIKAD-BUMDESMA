@extends('layouts.app')

@section('content')
@php
    $displayName = $employee?->display_name ?? $user->name;
    $words = preg_split('/\s+/', trim($displayName));
    $initials = strtoupper(mb_substr($words[0] ?? '-', 0, 1).mb_substr($words[1] ?? '', 0, 1));
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Profil Saya</h1>
        <p class="text-secondary mb-0">Kelola informasi dan akun Anda.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('profile.edit') }}">
        <i class="bi bi-pencil me-2">
        </i>Edit Profil</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 h-100">
            <div class="employee-avatar mx-auto mb-3" style="width:130px;height:130px;font-size:2.6rem">
                @if($employee?->photo)
                    <img src="{{ asset('storage/'.$employee->foto) }}" alt="Foto {{ $displayName }}">
                @else
                <span>
                    {{ $initials }}
                </span>
                @endif
            </div>
            <h2 class="h5 mb-1">{{ $displayName }}</h2>
            <p class="text-secondary mb-3">
                {{ $employee?->position?->nama ?? ($user->isAdmin() ? 'Administrator' : 'Belum ada jabatan') }}
            </p>
            <div class="d-flex justify-content-center gap-2">
                @if($employee)
                    <span class="badge text-bg-light border">{{ $employee->kode_karyawan }}</span>
                    <span class="badge text-bg-{{ $employee->aktif ? 'success' : 'secondary' }}">
                        {{ $employee->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                @else
                    <span class="badge text-bg-primary">Administrator</span>
                @endif
            </div>
            @if($photoCooldown ?? null)
                <p class="text-secondary small mt-3 mb-0">
                    <i class="bi bi-clock-history me-1"></i>Foto profil dapat diganti lagi pada
                    <strong>{{ $photoCooldown['available_at']->translatedFormat('d F Y') }}</strong>.
                </p>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    {{ $employee ? 'Informasi karyawan' : 'Informasi akun' }}
                </h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Nama Lengkap</small>
                        <strong>
                            {{ $displayName }}
                        </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Email</small>
                        <strong>
                            {{ $employee?->display_email ?? $user->email }}
                        </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Peran</small>
                        <strong>
                            {{ $user->isAdmin() ? 'Administrator' : 'Karyawan' }}
                        </strong>
                    </div>
                    @if($employee)
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">ID Karyawan</small>
                            <strong>
                                {{ $employee->kode_karyawan }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">Jabatan</small>
                            <strong>
                                {{ $employee->position?->nama ?? '-' }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">Nomor Telepon</small>
                            <strong>
                                {{ $employee->telepon ?: '-' }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">Tanggal Bergabung</small>
                            <strong>
                                {{ $employee->tanggal_masuk?->format('d F Y') ?? '-' }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">Jadwal Kerja</small>
                            <strong>
                                {{ $employee->workSchedule?->nama ?? '-' }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-secondary d-block">Status</small>
                            <span class="badge text-bg-{{ $employee->aktif ? 'success' : 'secondary' }}">
                                {{ $employee->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
