@extends('layouts.app')

@section('content')
@php
    $displayName = $employee?->display_name ?? $user->name;
    $words = preg_split('/\s+/', trim($displayName));
    $initials = strtoupper(mb_substr($words[0] ?? '-', 0, 1).mb_substr($words[1] ?? '', 0, 1));
@endphp
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('profile') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Profil Saya</a>
    <h1 class="h3 mt-3 mb-1">Edit Profil</h1>
    <p class="text-secondary mb-0">Perbarui informasi{{ $employee ? ', foto,' : '' }} dan keamanan akun Anda.</p>
</div>

<div class="row g-4">
    @if($employee)
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 h-100">
            <div
                class="employee-avatar mx-auto mb-3"
                id="photo-preview"
                style="width:130px;height:130px;font-size:2.6rem">
                @if($employee->foto)
                    <img src="{{ asset('storage/'.$employee->foto) }}" alt="Foto saat ini">
                @else
                <span>
                    {{ $initials }}
                </span>
                @endif
            </div>
            <h2 class="h6 mb-3">Foto Profil</h2>
            @if($photoCooldown ?? null)
                <div class="alert alert-info small text-start mb-0">
                    <i class="bi bi-clock-history me-1"></i>
                    Foto profil hanya dapat diganti 1 minggu sekali.
                    Dapat diganti lagi pada
                    <strong>{{ $photoCooldown['available_at']->translatedFormat('d F Y') }}</strong>
                    (<span
                        id="photo-cooldown-countdown"
                        data-available-at="{{ $photoCooldown['available_at']->toIso8601String() }}">{{ $photoCooldown['days_left'] }} hari lagi</span>).
                </div>
            @else
                <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data">
                    @csrf
                    <input
                        class="form-control text-start mb-2"
                        id="profile-photo"
                        type="file"
                        name="foto"
                        accept=".jpg,.jpeg,.png,.webp"
                        required>
                    <div
                        class="form-text text-start mb-3">JPG,
                        JPEG,
                        PNG,
                        WEBP
                        —
                        maksimal
                        2
                        MB.
                        Preview
                        tampil
                        otomatis.</div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-upload me-2">
                        </i>Simpan Foto</button>
                </form>
            @endif
            @if($employee->foto && ! ($photoCooldown ?? null))
                <form
                    method="POST"
                    action="{{ route('profile.photo') }}"
                    data-confirm="Foto profil akan dihapus. Lanjutkan?">
                    @csrf
                    <input type="hidden" name="hapus_foto" value="1">
                    <button class="btn btn-link text-danger text-decoration-none btn-sm mt-2" type="submit">
                        <i class="bi bi-trash me-1">
                        </i>Hapus foto</button>
                </form>
            @endif
        </div>
    </div>
    @endif

    <div class="{{ $employee ? 'col-lg-8' : 'col-lg-12' }}">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Informasi profil</h2>
            </div>
            <div class="card-body p-4">
                @if($employee)
                <div class="row g-3 mb-4">
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
                        <small class="text-secondary d-block">Tanggal Bergabung</small>
                        <strong>
                            {{ $employee->tanggal_masuk?->format('d F Y') ?? '-' }}
                        </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Status</small>
                        <span class="badge text-bg-{{ $employee->aktif ? 'success' : 'secondary' }}">
                            {{ $employee->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
                @endif
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $user->name) }}" required>@error('nama')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror</div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror</div>
                        @if($employee)
                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon</label>
                            <input class="form-control @error('telepon') is-invalid @enderror" name="telepon" value="{{ old('telepon', $employee->telepon) }}">@error('telepon')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-2">
                            </i>Simpan Perubahan</button>
                        <a class="btn btn-light border" href="{{ route('profile') }}">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Keamanan akun</h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Password Saat Ini</label>
                        <input class="form-control @error('current_password') is-invalid @enderror" type="password" name="current_password" autocomplete="current-password" required>@error('current_password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror</div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" minlength="8" autocomplete="new-password" required>@error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror<div class="form-text">Minimal 8 karakter dan berbeda dari password saat ini.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input
                            class="form-control"
                            type="password"
                            name="password_confirmation"
                            minlength="8"
                            autocomplete="new-password"
                            required>
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-key me-2">
                        </i>Simpan Password Baru</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelector('#profile-photo')?.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;
        document.querySelector('#photo-preview').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Preview foto profil">`;
    });

    // Hitung mundur cooldown foto profil (fallback teks server tetap tampil tanpa JS).
    (() => {
        const el = document.querySelector('#photo-cooldown-countdown');
        if (!el) return;
        const target = new Date(el.dataset.availableAt).getTime();
        if (Number.isNaN(target)) return;
        const pad = (n) => String(n).padStart(2, '0');
        const tick = () => {
            const diff = target - Date.now();
            if (diff <= 0) {
                el.textContent = 'sebentar lagi — muat ulang halaman ini';
                clearInterval(timer);
                return;
            }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor(diff % 86400000 / 3600000);
            const m = Math.floor(diff % 3600000 / 60000);
            el.textContent = d > 0 ? `${d} hari ${pad(h)} jam ${pad(m)} mnt lagi` : `${pad(h)} jam ${pad(m)} mnt lagi`;
        };
        const timer = setInterval(tick, 30000);
        tick();
    })();
</script>
@endpush
@endsection
