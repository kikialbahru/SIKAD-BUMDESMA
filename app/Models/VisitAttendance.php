<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAttendance extends Model
{
    public const MAX_PER_DAY = 10;

    protected $table = 'kunjungan';

    protected $fillable = ['karyawan_id', 'judul', 'deskripsi', 'foto', 'tanggal', 'lintang', 'bujur', 'akurasi'];

    protected $casts = ['tanggal' => 'date', 'lintang' => 'decimal:7', 'bujur' => 'decimal:7'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }
}
