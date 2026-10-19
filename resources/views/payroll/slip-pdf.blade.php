<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji {{ $periodLabel }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h1 { font-size: 18px; margin: 0; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .section { font-size: 13px; font-weight: bold; margin: 14px 0 6px; }
        .total td { font-weight: bold; font-size: 14px; }
        .sign { margin-top: 28px; }
        .sign td { border: none; text-align: center; width: 50%; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUMDESMA LKD TARUB</h1>
        <p>Slip Gaji Karyawan — {{ $periodLabel }}</p>
        <p class="muted">{{ $payroll->periode_mulai->translatedFormat('d F Y') }} s.d. {{ $payroll->periode_selesai->translatedFormat('d F Y') }}</p>
    </div>

    <table>
        <tr>
            <th style="width: 25%">Nama</th>
            <td>{{ $payroll->employee?->display_name ?? '-' }}</td>
            <th style="width: 25%">ID Karyawan</th>
            <td>{{ $payroll->employee?->kode_karyawan ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jabatan</th>
            <td>{{ $payroll->employee?->position?->nama ?? '-' }}</td>
            <th>Status</th>
            <td>@if($payroll->status === 'paid') Dibayar @elseif($payroll->status === 'verified') Diverifikasi @else Draft @endif</td>
        </tr>
    </table>

    <div class="section">Penerimaan</div>
    <table>
        <tr><td>Gaji pokok</td><td class="num">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}</td></tr>
        <tr><td>Tunjangan</td><td class="num">Rp {{ number_format($payroll->tunjangan_kehadiran, 0, ',', '.') }}</td></tr>
        @forelse($rewards as $reward)
            <tr><td>Reward — {{ $reward->judul }} ({{ $reward->diberikan_pada?->format('d M Y') }})</td><td class="num">Rp {{ number_format($reward->nominal, 0, ',', '.') }}</td></tr>
        @empty
            <tr><td>Reward</td><td class="num">Rp 0</td></tr>
        @endforelse
        <tr><td><strong>Total penerimaan</strong></td><td class="num"><strong>Rp {{ number_format($payroll->gaji_pokok + $payroll->tunjangan_kehadiran + $payroll->total_reward, 0, ',', '.') }}</strong></td></tr>
    </table>

    <div class="section">Potongan</div>
    <table>
        <tr><td>Keterlambatan ({{ $lateMinutes }} menit × Rp {{ number_format($lateRate, 0, ',', '.') }})</td><td class="num">Rp {{ number_format($payroll->potongan_telat, 0, ',', '.') }}</td></tr>
        <tr><td>Absen ({{ $absenceDays }} hari × Rp {{ number_format($absenceRate, 0, ',', '.') }})</td><td class="num">Rp {{ number_format($payroll->potongan_absen, 0, ',', '.') }}</td></tr>
        @forelse($punishments as $punishment)
            <tr><td>Hukuman — {{ $punishment->judul }} ({{ $punishment->dijatuhkan_pada?->format('d M Y') }})</td><td class="num">Rp {{ number_format($punishment->nominal, 0, ',', '.') }}</td></tr>
        @empty
            <tr><td>Hukuman</td><td class="num">Rp 0</td></tr>
        @endforelse
        <tr><td><strong>Total potongan</strong></td><td class="num"><strong>Rp {{ number_format($payroll->potongan_telat + $payroll->potongan_absen + $payroll->total_hukuman, 0, ',', '.') }}</strong></td></tr>
    </table>

    <table>
        <tr class="total"><td>Gaji bersih</td><td class="num">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}</td></tr>
    </table>

    <p class="muted">Kehadiran: {{ $presentDays }} hari hadir dari {{ $workdays }} hari kerja · terlambat {{ $lateDays }} hari ({{ $lateMinutes }} menit) · absen {{ $absenceDays }} hari.</p>

    <table class="sign">
        <tr>
            <td>Dibuat oleh,<br><br><br><br>( Admin )</td>
            <td>Diterima oleh,<br><br><br><br>( {{ $payroll->employee?->display_name ?? '-' }} )</td>
        </tr>
    </table>
</body>
</html>
