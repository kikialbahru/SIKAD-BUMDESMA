<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $table = 'pengaturan_kehadiran';

    protected $fillable = [
        'jam_masuk',
        'jam_pulang',
        'toleransi_telat_menit',
        'mulai_absen_pulang',
        'akhir_absen_pulang',
        'wajib_persetujuan_pulang',
        'poin_otomatis_aktif',
        'blok_menit_poin',
        'poin_per_blok',
    ];

    protected $casts = ['wajib_persetujuan_pulang' => 'boolean', 'poin_otomatis_aktif' => 'boolean'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:00',
            'toleransi_telat_menit' => 30,
            'mulai_absen_pulang' => '14:30',
            'akhir_absen_pulang' => '15:30',
            'wajib_persetujuan_pulang' => true,
            'poin_otomatis_aktif' => true,
            'blok_menit_poin' => 15,
            'poin_per_blok' => 1,
        ]);
    }
}
