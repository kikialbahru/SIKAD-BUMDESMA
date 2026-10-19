@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="cor_employee">Karyawan</label>
        <select
            class="form-select @error('karyawan_id') is-invalid @enderror"
            id="cor_employee"
            name="karyawan_id"
            required>
            <option value="">— Pilih karyawan —</option>
            @foreach($employees as $employee)
                <option
                    value="{{ $employee->id }}"
                    @selected((string) old('karyawan_id', $attendance->karyawan_id ?? '') === (string) $employee->id)>
                    {{ $employee->kode_karyawan }} — {{ $employee->display_name }}@unless($employee->aktif) (nonaktif)@endunless
                </option>
            @endforeach
        </select>
        @error('karyawan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_date">Tanggal</label>
        <input
            class="form-control @error('tanggal') is-invalid @enderror"
            id="cor_date"
            type="date"
            name="tanggal"
            value="{{ old('tanggal', isset($attendance) ? $attendance->tanggal->format('Y-m-d') : now()->toDateString()) }}"
            max="{{ now()->toDateString() }}"
            required>
        @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_in">Jam masuk</label>
        <input
            class="form-control @error('jam_masuk') is-invalid @enderror"
            id="cor_in"
            type="time"
            name="jam_masuk"
            value="{{ old('jam_masuk', isset($attendance) && $attendance->waktu_masuk ? $attendance->waktu_masuk->format('H:i') : '') }}">
        @error('jam_masuk')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_out">Jam pulang <span class="text-secondary">(boleh kosong)
            </span>
        </label>
        <input
            class="form-control @error('jam_pulang') is-invalid @enderror"
            id="cor_out"
            type="time"
            name="jam_pulang"
            value="{{ old('jam_pulang', isset($attendance) && $attendance->waktu_pulang ? $attendance->waktu_pulang->format('H:i') : '') }}">
        @error('jam_pulang')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="cor_status" name="status" required>
            @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'sick' => 'Sakit', 'duty' => 'Dinas Keluar', 'absent' => 'Alpa'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $attendance->status ?? 'present') === $value)>
                    {{ $label }}
                </option>
            @endforeach
            @if(old('status', $attendance->status ?? '') === 'permission')
                <option value="permission" selected>Izin (data lama)</option>
            @endif
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div
            class="form-text">Bila
            jam
            masuk
            diisi,
            status
            Hadir/Terlambat
            ditentukan
            otomatis
            dari
            jam
            kerja.
            Kosongkan
            kedua
            jam
            untuk
            Cuti/Izin/Sakit/Dinas/Alpa.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_note">Keterangan koreksi</label>
        <textarea
            class="form-control @error('catatan') is-invalid @enderror"
            id="cor_note"
            name="catatan"
            rows="3"
            maxlength="500"
            placeholder="cth. Lupa absen, HP mati, GPS bermasalah...">
        {{ old('catatan', $attendance->catatan ?? '') }}
        </textarea>
        @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
