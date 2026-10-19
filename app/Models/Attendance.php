<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'kehadiran';

    protected $fillable = ['karyawan_id', 'tanggal', 'waktu_masuk', 'waktu_pulang', 'menit_telat', 'menit_kerja', 'status', 'catatan', 'status_pulang', 'tipe_persetujuan_pulang', 'disetujui_pada', 'disetujui_oleh', 'foto_masuk', 'lintang_masuk', 'bujur_masuk', 'akurasi_masuk', 'jarak_masuk_meter', 'lokasi_masuk_valid', 'foto_pulang', 'lintang_pulang', 'bujur_pulang', 'akurasi_pulang', 'jarak_pulang_meter', 'lokasi_pulang_valid'];

    protected $casts = ['tanggal' => 'date', 'waktu_masuk' => 'datetime', 'waktu_pulang' => 'datetime', 'disetujui_pada' => 'datetime', 'lokasi_masuk_valid' => 'boolean', 'lokasi_pulang_valid' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'karyawan_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Flag anomali untuk tinjauan admin. Browser tidak dapat mendeteksi
     * mock-GPS/fake-location secara andal, jadi sistem menandai data yang
     * janggal (akurasi buruk, di luar radius, tanpa foto) alih-alih memblokir.
     *
     * @return string[]
     */
    public function anomalyFlags(): array
    {
        $flags = [];
        if ($this->waktu_masuk && ! $this->foto_masuk) {
            $flags[] = 'Masuk tanpa foto (input manual/koreksi)';
        }
        if ($this->akurasi_masuk !== null && $this->akurasi_masuk > 500) {
            $flags[] = 'Akurasi GPS masuk buruk (±'.$this->akurasi_masuk.' m)';
        }
        if ($this->lokasi_masuk_valid === false) {
            $flags[] = 'Masuk di luar radius kantor';
        }
        if ($this->waktu_pulang && ! $this->foto_pulang) {
            $flags[] = 'Pulang tanpa foto (input manual/koreksi)';
        }
        if ($this->akurasi_pulang !== null && $this->akurasi_pulang > 500) {
            $flags[] = 'Akurasi GPS pulang buruk (±'.$this->akurasi_pulang.' m)';
        }
        if ($this->lokasi_pulang_valid === false) {
            $flags[] = 'Pulang di luar radius kantor';
        }

        return $flags;
    }
}
