<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'karyawan';

    protected $fillable = ['pengguna_id', 'jabatan_id', 'jadwal_kerja_id', 'kode_karyawan', 'nama', 'telepon', 'email', 'foto', 'foto_diubah_pada', 'tanggal_masuk', 'gaji_pokok', 'tunjangan_kehadiran', 'aktif'];

    protected $casts = ['tanggal_masuk' => 'date', 'foto_diubah_pada' => 'datetime', 'gaji_pokok' => 'decimal:2', 'tunjangan_kehadiran' => 'decimal:2', 'aktif' => 'boolean'];

    protected $appends = ['display_name', 'display_email'];

    public function getDisplayNameAttribute(): string
    {
        return $this->nama ?: ($this->user?->name ?? '-');
    }

    public function getDisplayEmailAttribute(): ?string
    {
        return $this->email ?: $this->user?->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'jabatan_id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'jadwal_kerja_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'karyawan_id');
    }

    public function visitAttendances(): HasMany
    {
        return $this->hasMany(VisitAttendance::class, 'karyawan_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'karyawan_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'karyawan_id');
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(Punishment::class, 'karyawan_id');
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'karyawan_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'karyawan_id');
    }
}
