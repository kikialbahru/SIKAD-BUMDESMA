@extends('layouts.app')

@section('content')
    <div class="mb-4">
        @if (auth()->user()->isAdmin())
            <a class="text-decoration-none" href="{{ route('employees.index') }}">
                <i class="bi bi-arrow-left me-2">
                </i>Kembali
            ke Data Karyawan</a>
            @else
            <a class="text-decoration-none" href="{{ route('dashboard') }}">
                <i
                    class="bi bi-arrow-left me-2">
                    </i>Kembali ke Dashboard</a>
        @endif
    </div>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="row g-0">
            <div class="col-lg-4 bg-primary-subtle d-flex align-items-center justify-content-center p-5">
                <div class="text-center">
                    <div class="employee-avatar mx-auto mb-3">
                        @if ($employee->foto)
                            <img src="{{ asset('storage/' . $employee->foto) }}"
                            alt="Foto {{ $employee->display_name }}">
                            @else
                            <span>
                                {{ strtoupper(substr($employee->display_name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <h1 class="h4 mb-1">{{ $employee->display_name }}</h1>
                    <p class="text-secondary mb-0">{{ $employee->position?->nama ?? 'Belum ada jabatan' }}</p>
                </div>
            </div>
            <div class="col-lg-8 p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <p class="text-uppercase text-primary small fw-bold mb-2">Detail karyawan</p>
                        <h2 class="h3 mb-0">Informasi profil</h2>
                    </div>
                    <span
                        class="badge text-bg-{{ $employee->aktif ? 'success' : 'secondary' }}">
                        {{ $employee->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                </div>
                <div class="row g-4">
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">ID
                            Karyawan</small>
                            <strong>
                                {{ $employee->kode_karyawan }}
                            </strong>
                            </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Tanggal
                            Bergabung</small>
                            <strong>
                                {{ $employee->tanggal_masuk?->format('d F Y') ?? '-' }}
                            </strong>
                            </div>
                    <div class="col-sm-6">
                        <small
                            class="text-secondary d-block">Email</small>
                            <strong>
                                {{ $employee->display_email ?? '-' }}
                            </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Nomor
                            Telepon</small>
                            <strong>
                                {{ $employee->telepon ?: '-' }}
                            </strong>
                            </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Jadwal
                            Kerja</small>
                            <strong>
                                {{ $employee->workSchedule?->nama ?? '-' }}
                            </strong>
                            </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-5">
                    @if (auth()->user()->isAdmin())
                        <a class="btn btn-primary" href="{{ route('employees.edit', $employee) }}">
                            <i
                                class="bi bi-pencil me-2">
                                </i>Edit Data</a>
                                <a class="btn btn-light border"
                        href="{{ route('employees.index') }}">Kembali</a>@else<a class="btn btn-light border"
                            href="{{ route('dashboard') }}">Kembali</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
