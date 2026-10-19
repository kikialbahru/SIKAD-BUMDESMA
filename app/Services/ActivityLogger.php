<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Jejak audit aksi sensitif (koreksi absensi, review cuti, payroll,
 * reward/punishment). Tidak pernah menggagalkan alur utama: kegagalan
 * tulis log ditelan agar aksi bisnis tetap berjalan.
 */
class ActivityLogger
{
    public static function log(string $action, ?Model $subject = null, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'metadata' => $metadata ?: null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Audit bersifat best-effort; jangan ganggu aksi utama.
        }
    }
}
