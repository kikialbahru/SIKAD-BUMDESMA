@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('employees.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Data Karyawan</a>
    <h1 class="h3 mt-3 mb-1">{{ $employee->exists ? 'Edit Data Karyawan' : 'Tambah Karyawan' }}</h1>
    <p class="text-secondary mb-0">
        {{ $employee->exists ? 'Perbarui informasi profil, pengaturan kerja, dan akun karyawan.' : 'Lengkapi informasi karyawan baru.' }}
    </p>
</div>

<form
    method="POST"
    action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
    class="row g-4"
    id="employee-form"
    enctype="multipart/form-data">
    @csrf
    @if($employee->exists)
        @method('PUT')
    @endif

    <div class="col-lg-6 order-1 order-lg-1">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-person-badge me-2 text-primary">
                    </i>Informasi Profil</h2>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="employee-avatar employee-preview-avatar" style="width:84px;height:84px;font-size:2rem">
                        @if($employee->foto)
                            <img src="{{ asset('storage/'.$employee->foto) }}" alt="Foto saat ini">
                        @else
                            <span>
                                {{ strtoupper(substr($employee->display_name !== '-' ? $employee->display_name : 'K', 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <label for="photo" class="btn btn-outline-primary btn-sm mb-2">
                            <i class="bi bi-camera me-1">
                            </i>Ganti Foto</label>
                        <input class="d-none" id="photo" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        @if($employee->foto)
                            <button type="button" id="remove-photo-btn" class="btn btn-outline-danger btn-sm mb-2 ms-1">
                                <i class="bi bi-trash me-1">
                                </i>Hapus Foto</button>
                        @endif
                        <input type="checkbox" name="hapus_foto" value="1" id="remove_photo" class="d-none">
                        <div
                            class="form-text">JPG,
                            JPEG,
                            PNG,
                            WEBP
                            —
                            maksimal
                            20
                            MB.
                            Preview
                            tampil
                            sebelum
                            disimpan.</div>
                        @error('foto')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input
                        class="form-control @error('nama') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('nama', $employee->display_name !== '-' ? $employee->display_name : '') }}"
                        required>
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">ID Karyawan</label>
                    @if($employee->exists)
                        <input type="hidden" name="kode_karyawan" value="{{ $employee->kode_karyawan }}">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock">
                                </i>
                            </span>
                            <input class="form-control bg-light" value="{{ $employee->kode_karyawan }}" readonly>
                        </div>
                        <div class="form-text">ID karyawan dibuat otomatis dan tidak dapat diubah.</div>
                    @else
                        <input class="form-control bg-light" value="Dibuat otomatis (KRY-001)" readonly>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Nomor Telepon</label>
                        <input
                            class="form-control @error('telepon') is-invalid @enderror"
                            id="phone"
                            name="telepon"
                            value="{{ old('telepon', $employee->telepon) }}">
                        @error('telepon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="joined_at">Tanggal Bergabung</label>
                        <input
                            class="form-control @error('tanggal_masuk') is-invalid @enderror"
                            id="joined_at"
                            type="date"
                            name="tanggal_masuk"
                            value="{{ old('tanggal_masuk', $employee->tanggal_masuk?->format('Y-m-d')) }}"
                            required>
                        @error('tanggal_masuk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 order-2 order-lg-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-briefcase me-2 text-primary">
                    </i>Pengaturan Kerja</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="position_id">Jabatan</label>
                    <select
                        class="form-select @error('jabatan_id') is-invalid @enderror"
                        id="position_id"
                        name="jabatan_id"
                        required>
                        <option value="">Pilih jabatan</option>
                        @foreach($positions as $position)
                            <option
                                value="{{ $position->id }}"
                                @selected(old('jabatan_id', $employee->jabatan_id) == $position->id)>
                            {{ $position->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jabatan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="work_schedule_id">Jadwal Kerja</label>
                    <select
                        class="form-select @error('jadwal_kerja_id') is-invalid @enderror"
                        id="work_schedule_id"
                        name="jadwal_kerja_id">
                        <option value="">Pilih jadwal</option>
                        @foreach($schedules as $schedule)
                            <option
                                value="{{ $schedule->id }}"
                                @selected(old('jadwal_kerja_id', $employee->jadwal_kerja_id) == $schedule->id)>
                            {{ $schedule->nama }} ({{ substr($schedule->jam_masuk, 0, 5) }} - {{ substr($schedule->jam_pulang, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    @error('jadwal_kerja_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if($employee->exists)
                    @php($statusActive = (string) old('aktif', $employee->aktif ? '1' : '0') === '1')
                    <div class="mb-0">
                        <label class="form-label" for="is_active">Status Karyawan</label>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select" id="is_active" name="aktif">
                                <option value="1" @selected($statusActive)>Aktif</option>
                                <option value="0" @selected(! $statusActive)>Nonaktif</option>
                            </select>
                            <span
                                id="status-badge"
                                class="badge {{ $statusActive ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $statusActive ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 order-3 order-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-cash-coin me-2 text-primary">
                    </i>Kompensasi</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="basic_salary_display">Gaji Pokok</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            class="form-control"
                            id="basic_salary_display"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off">
                    </div>
                    <input
                        type="hidden"
                        name="gaji_pokok"
                        id="basic_salary"
                        value="{{ old('gaji_pokok', $employee->gaji_pokok ?? 0) }}">
                    @error('gaji_pokok')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="attendance_allowance_display">Tunjangan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            class="form-control"
                            id="attendance_allowance_display"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off">
                    </div>
                    <input
                        type="hidden"
                        name="tunjangan_kehadiran"
                        id="attendance_allowance"
                        value="{{ old('tunjangan_kehadiran', $employee->tunjangan_kehadiran ?? 0) }}">
                    @error('tunjangan_kehadiran')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-text mt-2">Hanya Admin yang dapat melihat dan mengubah kompensasi.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 order-4 order-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-shield-lock me-2 text-primary">
                    </i>Akun dan Keamanan</h2>
            </div>
            <div class="card-body">
                @if($employee->exists && $employee->user)
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-check-circle-fill">
                        </i>
                        <span>Akun <strong>Aktif dan Terhubung</strong> dengan role Karyawan.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="account-email">Email Login</label>
                        <input
                            class="form-control @error('email') is-invalid @enderror"
                            id="account-email"
                            type="email"
                            name="email"
                            value="{{ old('email', $employee->display_email) }}"
                            required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input class="form-control bg-light" value="Karyawan (otomatis, dikelola sistem)" readonly>
                    </div>
                    <button
                        class="btn btn-outline-secondary w-100"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#password-collapse"
                        aria-expanded="false">
                        <i class="bi bi-lock me-2">
                        </i>Ubah Password
                    </button>
                    <div class="collapse mt-3" id="password-collapse">
                        <div class="mb-3">
                            <label class="form-label" for="account-password">Password Baru</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="account-password"
                                type="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimal 8 karakter. Kosongkan jika tidak diubah.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="account-password-confirm">Konfirmasi Password Baru</label>
                            <input
                                class="form-control"
                                id="account-password-confirm"
                                type="password"
                                name="password_confirmation"
                                minlength="8"
                                autocomplete="new-password">
                        </div>
                    </div>
                @else
                    <div class="form-check mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="create_account"
                            value="1"
                            id="create_account"
                            @checked(old('create_account'))>
                        <label
                            class="form-check-label fw-semibold"
                            for="create_account">Buatkan
                            Akun
                            Login
                            untuk
                            Karyawan</label>
                        <div
                            class="form-text">Jika
                            dicentang,
                            sistem
                            membuat
                            akun
                            User
                            dengan
                            role
                            Karyawan
                            yang
                            terhubung
                            ke
                            data
                            ini.</div>
                    </div>
                    <div id="account-fields" class="row g-3" style="{{ old('create_account') ? '' : 'display:none' }}">
                        <div class="col-md-6">
                            <label class="form-label" for="new-email">Email Login</label>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                id="new-email"
                                type="email"
                                name="email"
                                value="{{ old('email', $employee->display_email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input class="form-control bg-light" value="Karyawan (otomatis)" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-password">Password</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="new-password"
                                type="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-password-confirm">Konfirmasi Password</label>
                            <input
                                class="form-control"
                                id="new-password-confirm"
                                type="password"
                                name="password_confirmation"
                                minlength="8"
                                autocomplete="new-password">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 order-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap align-items-center gap-2 justify-content-between">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light border" href="{{ route('employees.index') }}">
                        <i class="bi bi-arrow-left me-2">
                        </i>Kembali</a>
                    <a
                        class="btn btn-light border"
                        href="{{ $employee->exists ? route('employees.show', $employee) : route('employees.index') }}">Batal</a>
                </div>
                <button class="btn btn-primary px-4" id="save-btn" type="submit">
                    <i class="bi bi-save me-2">
                    </i>Simpan Perubahan</button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.querySelector('#photo')?.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;
        const removeBox = document.querySelector('#remove_photo');
        if (removeBox) removeBox.checked = false;
        const removeBtn = document.querySelector('#remove-photo-btn');
        if (removeBtn) removeBtn.classList.remove('active');
        document.querySelector('.employee-preview-avatar').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Preview foto profil">`;
    });
    document.querySelector('#remove-photo-btn')?.addEventListener('click', (event) => {
        const removeBox = document.querySelector('#remove_photo');
        const fileInput = document.querySelector('#photo');
        removeBox.checked = !removeBox.checked;
        event.currentTarget.classList.toggle('active', removeBox.checked);
        event.currentTarget.innerHTML = removeBox.checked
            ? '<i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan Hapus'
            : '<i class="bi bi-trash me-1"></i>Hapus Foto';
        if (removeBox.checked && fileInput) fileInput.value = '';
    });
    (function () {
        const checkbox = document.querySelector('#create_account');
        const fields = document.querySelector('#account-fields');
        if (!checkbox || !fields) return;
        const sync = () => {
            const on = checkbox.checked;
            fields.style.display = on ? '' : 'none';
            fields.querySelectorAll('input[name="email"], input[name="password"]').forEach((input) => {
                if (on) input.setAttribute('required', 'required');
                else input.removeAttribute('required');
            });
        };
        checkbox.addEventListener('change', sync);
        sync();
    })();
    (function () {
        const format = (value) => {
            const num = Math.round(parseFloat(String(value).replace(/[^0-9.]/g, '')) || 0);
            return new Intl.NumberFormat('id-ID').format(num);
        };
        const bind = (displayId, hiddenId) => {
            const display = document.getElementById(displayId);
            const hidden = document.getElementById(hiddenId);
            if (!display || !hidden) return;
            display.value = format(hidden.value);
            display.addEventListener('input', () => {
                const digits = display.value.replace(/\D/g, '').slice(0, 15);
                hidden.value = digits === '' ? 0 : parseInt(digits, 10);
                display.value = digits === '' ? '' : new Intl.NumberFormat('id-ID').format(parseInt(digits, 10));
            });
        };
        bind('basic_salary_display', 'basic_salary');
        bind('attendance_allowance_display', 'attendance_allowance');
    })();
    (function () {
        const select = document.querySelector('#is_active');
        const badge = document.querySelector('#status-badge');
        if (!select || !badge) return;
        select.addEventListener('change', () => {
            const active = select.value === '1';
            badge.textContent = active ? 'Aktif' : 'Nonaktif';
            badge.classList.toggle('text-bg-success', active);
            badge.classList.toggle('text-bg-secondary', !active);
        });
    })();
    document.querySelector('#employee-form')?.addEventListener('submit', (event) => {
        const btn = document.querySelector('#save-btn');
        if (!event.target.checkValidity()) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    });
</script>
@endpush
@endsection
