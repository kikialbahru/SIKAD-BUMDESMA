<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $table = 'komponen_gaji';

    protected $fillable = ['karyawan_id', 'nama', 'jenis', 'nominal', 'aktif'];

    protected $casts = ['nominal' => 'decimal:2', 'aktif' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }
}
