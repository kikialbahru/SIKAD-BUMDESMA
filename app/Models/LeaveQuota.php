<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveQuota extends Model
{
    protected $table = 'kuota_cuti';

    protected $fillable = ['tahun', 'jatah_hari'];

    public static function current(): self
    {
        return static::firstOrCreate(['tahun' => (int) now()->format('Y')], ['jatah_hari' => 12]);
    }

    /**
     * Quota usage for approved/pending CUTI (type=leave) only.
     * Pending requests never reduce the remaining quota permanently.
     */
    public static function usage(Employee $employee, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $entitlement = static::firstOrCreate(['tahun' => $year], ['jatah_hari' => 12])->jatah_hari;

        $days = fn (string $status) => (int) LeaveRequest::where('karyawan_id', $employee->id)
            ->where('jenis', 'leave')
            ->where('status', $status)
            ->whereYear('tanggal_mulai', $year)
            ->sum('jumlah_hari');

        $used = $days('approved');
        $pending = $days('pending');

        return [
            'entitlement' => $entitlement,
            'used' => $used,
            'pending' => $pending,
            'remaining' => max(0, $entitlement - $used),
        ];
    }
}
