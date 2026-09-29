<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $table = 'pengajuan_cuti';

    protected $fillable = ['karyawan_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_hari', 'alasan', 'jalur_lampiran', 'status', 'diulas_oleh', 'diulas_pada', 'catatan_ulasan', 'alasan_penolakan'];

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'diulas_pada' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diulas_oleh');
    }
}
