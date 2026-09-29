<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = ['tanggal', 'nama'];

    protected $casts = ['tanggal' => 'date'];
}
