<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected $table = 'jadwal_kerja';

    protected $fillable = ['nama', 'jam_masuk', 'jam_pulang', 'toleransi_telat_menit', 'hari_kerja', 'aktif'];

    protected $casts = ['hari_kerja' => 'array', 'aktif' => 'boolean'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'jadwal_kerja_id');
    }
}
