@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Log Aktivitas</h1>
        <p class="text-secondary mb-0">Jejak audit aksi sensitif: koreksi absensi, review cuti, payroll, reward & punishment.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="action">Aksi</label>
                <select class="form-select" id="action" name="action">
                    <option value="">Semua aksi</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-4">
                <label class="form-label small" for="search">Pelaku (nama/email)</label>
                <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100">
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-2"></i>Tampilkan</button>
                <a class="btn btn-light border" href="{{ route('activity-logs.index') }}" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu</th>
                    <th>Pelaku</th>
                    <th>Aksi</th>
                    <th>Subjek</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap"><small>{{ $log->created_at->translatedFormat('d M Y H:i') }}</small></td>
                        <td><strong class="d-block">{{ $log->user?->name ?? 'Sistem' }}</strong><small class="text-secondary">{{ $log->user?->email ?? '-' }}</small></td>
                        <td><span class="badge text-bg-light border">{{ $log->action }}</span></td>
                        <td><small class="text-secondary">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '-' }}</small></td>
                        <td><small class="text-secondary">
                            @if($log->metadata)
                                {{ collect($log->metadata)->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v)))->implode('; ') }}
                            @else
                                -
                            @endif
                        </small></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5"><i class="bi bi-journal-text d-block fs-2 mb-2"></i>Belum ada log aktivitas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $logs->links() }}</div>
</div>
@endsection
