@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Reward & Punishment</h1>
            <p class="text-secondary mb-0">Apresiasi dan pembinaan karyawan secara terukur.</p>
        </div>
        @if (auth()->user()->isAdmin())
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rewardModal">
                    <i
                        class="bi bi-trophy me-2">
                        </i>Tambah reward</button>
                        <button class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#punishmentModal">
                    <i
                        class="bi bi-exclamation-triangle me-2">
                        </i>Tambah punishment</button>
                        </div>
        @endif
    </div>
    @if (auth()->user()->isAdmin())
        <form class="card border-0 shadow-sm mb-4" method="GET">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Filter karyawan</label>
                    <select class="form-select"
                        name="karyawan_id">
                        <option value="">Semua karyawan</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($selectedEmployee == $employee->id)>
                                {{ $employee->kode_karyawan }}
                                - {{ $employee->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-primary" type="submit">Terapkan filter</button>
                </div>
            </div>
        </form>
    @endif
    @if (auth()->user()->isAdmin())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-8">
                    <h2 class="h6 mb-1">
                        <i class="bi bi-lightning me-2 text-warning">
                        </i>Poin keterlambatan otomatis</h2>
                    <p class="small text-secondary mb-0">
                        @if ($pointSettings->poin_otomatis_aktif)
                            Aturan aktif: setiap {{ $pointSettings->blok_menit_poin }} menit
                            = {{ $pointSettings->poin_per_blok }} poin (bagian blok dihitung penuh).
                        @else
                            Aturan sedang <strong>nonaktif</strong>.
                        @endif
                        Generate ulang periode yang sama akan mengganti hasil lama (input manual tidak tersentuh).
                        Atur di <a href="{{ route('attendance.settings') }}">Pengaturan Absensi</a>.
                    </p>
                </div>
                <div class="col-md-4">
                    <form method="POST" action="{{ route('recognition.auto-points') }}" class="d-flex gap-2">
                        @csrf
                        <input
                            class="form-control"
                            type="month"
                            name="month"
                            value="{{ now()->format('Y-m') }}"
                            required>
                        <button class="btn btn-warning text-nowrap" type="submit">
                            <i class="bi bi-lightning me-1">
                            </i>Generate</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">
                        <i class="bi bi-trophy text-success me-2">
                        </i>Riwayat reward</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($rewards as $reward)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong>
                                        {{ $reward->judul }}
                                    </strong>
                                    <small
                                        class="d-block text-secondary">{{ $reward->employee?->user?->name }} ·
                                        {{ $reward->diberikan_pada->format('d M Y') }}</small>
                                    @if ($reward->deskripsi)
                                        <p class="small text-secondary mb-0 mt-2">{{ $reward->deskripsi }}</p>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span
                                        class="badge text-bg-success">
                                        {{ $reward->jenis === 'points' ? $reward->poin . ' poin' : 'Rp ' . number_format($reward->nominal, 0, ',', '.') }}
                                        </span>
                                    @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('recognition.rewards.destroy', $reward) }}"
                                            class="mt-2">@csrf @method('DELETE')<button
                                                class="btn btn-sm btn-danger rounded-pill px-3" type="submit"
                                                onclick="return confirm('Hapus reward ini?')">
                                                <i
                                                    class="bi bi-trash3-fill me-1">
                                                    </i>Hapus</button>
                                                    </form>
                                    @endif
                                </div>
                            </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-secondary">
                        <i
                                class="bi bi-trophy fs-2 d-block mb-2">
                                </i>Belum ada reward.</div>
                    @endforelse
                </div>
                <div class="p-3">{{ $rewards->links() }}</div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">
                        <i class="bi bi-exclamation-triangle text-danger me-2">
                        </i>Riwayat punishment</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($punishments as $punishment)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong>
                                        {{ $punishment->judul }}
                                    </strong>
                                    @if ($punishment->otomatis)
                                        <span
                                            class="badge text-bg-info ms-1"
                                            title="Digenerate otomatis dari keterlambatan">Otomatis</span>
                                    @endif
                                    <small
                                        class="d-block text-secondary">{{ $punishment->employee?->user?->name }} ·
                                        {{ $punishment->dijatuhkan_pada->format('d M Y') }}</small>
                                    @if ($punishment->deskripsi)
                                        <p class="small text-secondary mb-0 mt-2">{{ $punishment->deskripsi }}</p>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span
                                        class="badge text-bg-danger">
                                        {{ $punishment->jenis === 'salary_deduction' ? 'Rp ' . number_format($punishment->nominal, 0, ',', '.') : ($punishment->jenis === 'points_deduction' && $punishment->poin > 0 ? $punishment->poin . ' poin' : ucfirst(str_replace('_', ' ', $punishment->jenis))) }}
                                        </span>
                                    @if (auth()->user()->isAdmin())
                                        <form method="POST"
                                            action="{{ route('recognition.punishments.destroy', $punishment) }}"
                                            class="mt-2"
                                            data-confirm="Hapus punishment {{ $punishment->judul }}? Data yang dihapus tidak dapat dikembalikan.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger rounded-pill px-3"
                                                type="submit">
                                                <i class="bi bi-trash3-fill me-1">
                                                </i>Hapus</button>
                                                </form>
                                    @endif
                                </div>
                            </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-secondary">
                        <i
                                class="bi bi-check-circle fs-2 d-block mb-2">
                                </i>Belum ada punishment.</div>
                    @endforelse
                </div>
                <div class="p-3">{{ $punishments->links() }}</div>
            </div>
        </div>
    </div>
    @if (auth()->user()->isAdmin())
        <div class="modal fade" id="rewardModal" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('recognition.rewards.store') }}">@csrf<div
                        class="modal-header">
                        <h5 class="modal-title">Tambah reward</h5>
                        <button class="btn-close"
                            data-bs-dismiss="modal">
                            </button>
                    </div>
                    <div class="modal-body">
                        <select class="form-select mb-3" name="karyawan_id" required>
                            <option value="">Pilih karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->kode_karyawan }} -
                                    {{ $employee->user?->name }}</option>
                            @endforeach
                        </select>
                        <input class="form-control mb-3" name="judul" placeholder="Judul reward" required>
                        <select
                            class="form-select mb-3" name="jenis">
                            <option value="bonus">Bonus nominal</option>
                            <option value="points">Poin reward</option>
                        </select>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input class="form-control" name="nominal" type="number" min="0"
                                    placeholder="Nominal bonus">
                                    </div>
                            <div class="col-6">
                                <input class="form-control" name="poin" type="number" min="0"
                                    placeholder="Poin">
                                    </div>
                        </div>
                        <input class="form-control mb-3" name="diberikan_pada" type="date"
                            value="{{ today()->format('Y-m-d') }}" required>
                        <textarea class="form-control" name="deskripsi" rows="3" placeholder="Keterangan">
                        </textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" type="submit">Simpan reward</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="punishmentModal" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('recognition.punishments.store') }}">@csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah punishment</h5>
                        <button class="btn-close"
                            data-bs-dismiss="modal">
                            </button>
                    </div>
                    <div class="modal-body">
                        <select class="form-select mb-3" name="karyawan_id" required>
                            <option value="">Pilih karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->kode_karyawan }} -
                                    {{ $employee->user?->name }}</option>
                            @endforeach
                        </select>
                        <input class="form-control mb-3" name="judul" placeholder="Judul punishment"
                            required>
                            <select class="form-select mb-3" name="jenis">
                            <option value="warning">Peringatan</option>
                            <option value="points_deduction">Pengurangan poin</option>
                            <option value="salary_deduction">Potongan gaji</option>
                            <option value="administrative">Catatan administratif</option>
                        </select>
                        <input class="form-control mb-3" name="nominal" type="number" min="0"
                            placeholder="Nominal potongan">
                            <input class="form-control mb-3" name="dijatuhkan_pada"
                            type="date" value="{{ today()->format('Y-m-d') }}" required>
                        <textarea class="form-control" name="deskripsi" rows="3" placeholder="Keterangan">
                        </textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" type="submit">Simpan punishment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
