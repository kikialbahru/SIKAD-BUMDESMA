<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $table = 'penggajian';

    protected $fillable = [
        'karyawan_id', 'periode_mulai', 'periode_selesai', 'gaji_pokok', 'tunjangan_kehadiran',
        'total_reward', 'potongan_telat', 'potongan_absen', 'total_hukuman', 'gaji_bersih', 'status',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'gaji_pokok' => 'decimal:2',
        'tunjangan_kehadiran' => 'decimal:2',
        'total_reward' => 'decimal:2',
        'potongan_telat' => 'decimal:2',
        'potongan_absen' => 'decimal:2',
        'total_hukuman' => 'decimal:2',
        'gaji_bersih' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }
}
