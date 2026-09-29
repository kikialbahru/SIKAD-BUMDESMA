<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Punishment extends Model
{
    protected $table = 'hukuman';

    protected $fillable = ['karyawan_id', 'punishment_rule_id', 'jenis', 'nominal', 'poin', 'otomatis', 'judul', 'deskripsi', 'dijatuhkan_pada'];

    protected $casts = ['nominal' => 'decimal:2', 'dijatuhkan_pada' => 'date', 'otomatis' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }
}
