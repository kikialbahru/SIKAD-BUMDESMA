<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $table = 'penghargaan';

    protected $fillable = ['karyawan_id', 'reward_rule_id', 'jenis', 'nominal', 'poin', 'judul', 'deskripsi', 'diberikan_pada'];

    protected $casts = ['nominal' => 'decimal:2', 'diberikan_pada' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }
}
