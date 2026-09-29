<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLocation extends Model
{
    protected $table = 'lokasi_kehadiran';

    protected $fillable = [
        'nama', 'alamat', 'lintang', 'bujur',
        'radius_meter', 'maks_akurasi_meter', 'wajib_radius', 'wajib_radius_pulang', 'aktif',
    ];

    protected $casts = [
        'lintang' => 'float',
        'bujur' => 'float',
        'wajib_radius' => 'boolean',
        'wajib_radius_pulang' => 'boolean',
        'aktif' => 'boolean',
    ];

    public static function active(): ?self
    {
        return static::where('aktif', true)->orderBy('id')->first();
    }
}
