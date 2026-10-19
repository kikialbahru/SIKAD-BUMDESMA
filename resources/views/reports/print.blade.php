<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan {{ $filters['type'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 18px 22px 32px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            font-size: 11px;
            margin: 0;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #1e5aa8;
            margin-bottom: 14px;
        }

        .kop-table td {
            vertical-align: middle;
            padding: 0 0 10px;
        }

        .kop-logo {
            width: 60px;
        }

        .kop-logo img {
            width: 52px;
            height: 52px;
        }

        h1 {
            color: #123b72;
            font-size: 22px;
            margin: 0;
        }

        .kop-title {
            display: inline-block;
            background: #1e5aa8;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
            padding: 3px 12px;
            margin-top: 4px;
        }

        .meta {
            color: #64748b;
            font-size: 10px;
            margin-top: 4px;
        }

        .section-title {
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            padding: 7px 12px;
            margin: 18px 0 0;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        table.data th,
        table.data td {
            border: 1px solid #d7e2eb;
            padding: 6px 8px;
            text-align: left;
        }

        table.data th {
            color: #fff;
            font-size: 11px;
        }

        table.data tr.zebra td {
            background: #f1f6fc;
        }

        table.data tr.total td {
            background: #e2e8f0;
            font-weight: bold;
            border-top: 3px double #15803d;
        }

        .num {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .pill {
            display: inline-block;
            font-weight: bold;
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 4px;
        }

        .summary-table td {
            width: 33%;
            border: 1px solid #dbe8f2;
            padding: 10px 12px;
        }

        .summary-table small {
            color: #64748b;
            font-size: 10px;
        }

        .summary-table strong {
            display: block;
            font-size: 16px;
            margin-top: 4px;
        }

        .footer {
            position: fixed;
            bottom: -22px;
            left: 0;
            right: 0;
            border-top: 1px solid #d7e2eb;
            padding-top: 6px;
            color: #64748b;
            font-size: 9px;
        }

        .footer .pagenum:after {
            content: counter(page);
        }

        .footer .pagecount:after {
            content: counter(pages);
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $pill = fn($bg, $fg) => "background:#{$bg};color:#{$fg};";
        $leaveType = fn($t) => $t === 'sick' ? 'Sakit' : ($t === 'duty' ? 'Dinas Keluar' : 'Cuti');
        $leaveStatus = fn($s) => match ($s) {
            'approved' => ['Disetujui', 'D1FAE5', '065F46'],
            'rejected' => ['Ditolak', 'FEE2E2', '991B1B'],
            default => ['Menunggu', 'FEF3C7', '92400E'],
        };
        $payrollStatus = fn($s) => match ($s) {
            'paid' => ['Dibayar', 'D1FAE5', '065F46'],
            'verified' => ['Diverifikasi', 'DBEAFE', '1D4ED8'],
            default => ['Draft', 'FEF3C7', '92400E'],
        };
        $pct = fn($v) => $v >= 80 ? ['D1FAE5', '065F46'] : ($v >= 50 ? ['FEF3C7', '92400E'] : ['FEE2E2', '991B1B']);
        $activePill = fn($a) => $a ? ['Aktif', 'D1FAE5', '065F46'] : ['Nonaktif', 'E2E8F0', '475569'];
        $rp = fn($v) => 'Rp '.number_format($v, 0, ',', '.');
        $scopeLine = !empty($filters['karyawan_id']) && $employees->count() === 1
            ? ' · Karyawan: '.$employees->first()->kode_karyawan.' - '.$employees->first()->display_name : '';
        $statusLine = !empty($filters['status']) ? ' · Status: '.$filters['status'] : '';
    @endphp
    <table class="kop-table">
        <tr>
            <td class="kop-logo"><img src="{{ public_path('images/logo-bumdesma.png') }}" alt="Logo"></td>
            <td>
                <h1>BUMDESMA LKD TARUB</h1>
                <div class="kop-title">LAPORAN {{ strtoupper(str_replace('-', ' ', $filters['type'])) }}</div>
                <div class="meta">Periode: {{ $filters['period_label'] ?? $filters['start']->translatedFormat('F Y') }} · Dicetak:
                    {{ now()->format('d/m/Y H:i') }}{{ $scopeLine }}{{ $statusLine }}</div>
            </td>
        </tr>
    </table>
    @if ($filters['type'] === 'attendance' || $filters['type'] === 'all')
        <div class="section-title" style="background:#1E5AA8">Laporan Kehadiran</div>
        <table class="data">
            <thead>
                <tr style="background:#1E5AA8">
                    <th class="center">No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th class="num">Hadir</th>
                    <th class="num">Terlambat</th>
                    <th class="num">Izin</th>
                    <th class="num">Cuti</th>
                    <th class="num">Dinas</th>
                    <th class="num">Total Hari</th>
                    <th class="num">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendanceSummary as $index => $item)
                    @php([$pbg, $pfg] = $pct($item['percentage']))
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item['employee']->kode_karyawan }}</td>
                        <td>{{ $item['employee']->display_name }}</td>
                        <td>{{ $item['employee']->position?->nama ?? '-' }}</td>
                        <td class="num">{{ $item['present'] }}</td>
                        <td class="num">{{ $item['late'] }}</td>
                        <td class="num">{{ $item['permission'] }}</td>
                        <td class="num">{{ $item['leave'] }}</td>
                        <td class="num">{{ $item['duty'] ?? 0 }}</td>
                        <td class="num">{{ $item['total'] }}</td>
                        <td class="num"><span class="pill" style="{{ $pill($pbg, $pfg) }}">{{ $item['percentage'] }}%</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    @if ($filters['type'] === 'leaves' || $filters['type'] === 'all')
        <div class="section-title" style="background:#0E7C86">Laporan Cuti &amp; Izin</div>
        <table class="data">
            <thead>
                <tr style="background:#0E7C86">
                    <th class="center">No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Durasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaves as $index => $item)
                    @php([$label, $bg, $fg] = $leaveStatus($item->status))
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->employee?->kode_karyawan }}</td>
                        <td>{{ $item->employee?->user?->name }}</td>
                        <td>{{ $leaveType($item->jenis) }}</td>
                        <td>{{ $item->tanggal_mulai->format('d/m/Y') }}</td>
                        <td>{{ $item->tanggal_selesai->format('d/m/Y') }}</td>
                        <td>{{ $item->jumlah_hari }} hari</td>
                        <td><span class="pill" style="{{ $pill($bg, $fg) }}">{{ $label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    @if ($filters['type'] === 'payroll' || $filters['type'] === 'all')
        <div class="section-title" style="background:#15803D">Laporan Penggajian</div>
        <table class="summary-table">
            <tr>
                <td><small>Total Reward</small><strong style="color:#15803D">{{ $rp($payrollSummary['reward']) }}</strong></td>
                <td><small>Total Potongan</small><strong style="color:#B91C1C">{{ $rp($payrollSummary['deduction']) }}</strong></td>
                <td><small>Gaji Bersih</small><strong style="color:#1E5AA8">{{ $rp($payrollSummary['net']) }}</strong></td>
            </tr>
        </table>
        <table class="data">
            <thead>
                <tr style="background:#15803D">
                    <th class="center">No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Periode</th>
                    <th class="num">Gaji Pokok</th>
                    <th class="num">Reward</th>
                    <th class="num">Potongan</th>
                    <th class="num">Gaji Bersih</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payrolls as $index => $item)
                    @php([$label, $bg, $fg] = $payrollStatus($item->status))
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->employee?->kode_karyawan }}</td>
                        <td>{{ $item->employee?->user?->name }}</td>
                        <td>{{ $item->periode_mulai->format('m/Y') }}</td>
                        <td class="num">{{ $rp($item->gaji_pokok) }}</td>
                        <td class="num">{{ $rp($item->total_reward) }}</td>
                        <td class="num">{{ $rp($item->potongan_telat + $item->potongan_absen + $item->total_hukuman) }}</td>
                        <td class="num"><strong>{{ $rp($item->gaji_bersih) }}</strong></td>
                        <td><span class="pill" style="{{ $pill($bg, $fg) }}">{{ $label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
                @if ($payrolls->isNotEmpty())
                    <tr class="total">
                        <td colspan="4" class="center">TOTAL</td>
                        <td class="num">{{ $rp($payrolls->sum('gaji_pokok')) }}</td>
                        <td class="num">{{ $rp($payrollSummary['reward']) }}</td>
                        <td class="num">{{ $rp($payrollSummary['deduction']) }}</td>
                        <td class="num">{{ $rp($payrollSummary['net']) }}</td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif
    @if ($filters['type'] === 'reward-punishment' || $filters['type'] === 'all')
        <div class="section-title" style="background:#B45309">Laporan Reward &amp; Punishment</div>
        <table class="data">
            <thead>
                <tr style="background:#B45309">
                    <th class="center">No</th>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th>Nilai</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rewards as $index => $item)
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->employee?->user?->name }}</td>
                        <td><span class="pill" style="{{ $pill('D1FAE5', '065F46') }}">Reward</span></td>
                        <td>{{ $item->judul }}</td>
                        <td>{{ $rp($item->nominal) }}</td>
                        <td>{{ $item->diberikan_pada->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach @foreach ($punishments as $index => $item)
                        @php($no = $rewards->count() + $index + 1)
                        <tr @class(['zebra' => ($no - 1) % 2 === 1])>
                            <td class="center">{{ $no }}</td>
                            <td>{{ $item->employee?->user?->name }}</td>
                            <td><span class="pill" style="{{ $pill('FEE2E2', '991B1B') }}">Punishment</span></td>
                            <td>{{ $item->judul }}</td>
                            <td>{{ $item->jenis === 'points_deduction' && $item->poin > 0 ? $item->poin . ' poin' : $rp($item->nominal) }}</td>
                            <td>{{ $item->dijatuhkan_pada->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                    @if ($rewards->isEmpty() && $punishments->isEmpty())
                        <tr>
                            <td colspan="6" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                        </tr>
                    @endif
            </tbody>
        </table>
    @endif
    @if ($filters['type'] === 'visits' || $filters['type'] === 'all')
        <div class="section-title" style="background:#7C3AED">Laporan Kunjungan</div>
        <table class="data">
            <thead>
                <tr style="background:#7C3AED">
                    <th class="center">No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Judul Kunjungan</th>
                    <th>Deskripsi</th>
                    <th>Tanggal</th>
                    <th>Koordinat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($visits as $index => $item)
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->employee?->kode_karyawan }}</td>
                        <td>{{ $item->employee?->user?->name }}</td>
                        <td>{{ $item->judul }}</td>
                        <td>{{ $item->deskripsi ?? '-' }}</td>
                        <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                        <td>
                            @if (!empty($filters['karyawan_id']) && $item->lintang && $item->bujur)
                                {{ number_format($item->lintang, 5) }}, {{ number_format($item->bujur, 5) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    @if ($filters['type'] === 'employees' || $filters['type'] === 'all')
        <div class="section-title" style="background:#475569">Laporan Data Karyawan</div>
        <table class="data">
            <thead>
                <tr style="background:#475569">
                    <th class="center">No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Bergabung</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $index => $item)
                    @php([$label, $bg, $fg] = $activePill($item->aktif))
                    <tr @class(['zebra' => $index % 2 === 1])>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->kode_karyawan }}</td>
                        <td>{{ $item->display_name }}</td>
                        <td>{{ $item->position?->nama ?? '-' }}</td>
                        <td>{{ $item->tanggal_masuk?->format('d/m/Y') ?? '-' }}</td>
                        <td><span class="pill" style="{{ $pill($bg, $fg) }}">{{ $label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="center" style="color:#64748b;padding:16px">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    <div class="footer">
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td>Laporan Sistem Informasi BUMDESMA LKD TARUB</td>
                <td style="text-align:right">Halaman <span class="pagenum"></span> dari <span class="pagecount"></span></td>
            </tr>
        </table>
    </div>
</body>

</html>
