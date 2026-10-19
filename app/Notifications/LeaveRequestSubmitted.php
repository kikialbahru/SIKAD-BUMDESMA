<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lr = $this->leaveRequest;
        $jenis = $lr->jenis === 'sick' ? 'sakit' : ($lr->jenis === 'duty' ? 'dinas keluar' : 'cuti');

        return (new MailMessage)
            ->subject('Pengajuan '.$jenis.' baru — '.($lr->employee->nama ?? 'Karyawan'))
            ->greeting('Halo Admin,')
            ->line(($lr->employee->nama ?? 'Karyawan').' mengajukan '.$jenis.' pada '.
                $lr->tanggal_mulai->translatedFormat('d M Y').' – '.$lr->tanggal_selesai->translatedFormat('d M Y').
                ' ('.$lr->jumlah_hari.' hari).')
            ->line('Alasan: '.$lr->alasan)
            ->action('Tinjau pengajuan', route('leave.show', $lr->id));
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $jenis = $lr->jenis === 'sick' ? 'sakit' : ($lr->jenis === 'duty' ? 'dinas keluar' : 'cuti');

        return [
            'title' => 'Pengajuan '.$jenis.' baru',
            'body' => ($lr->employee->nama ?? 'Karyawan').' mengajukan '.$jenis.' '.
                $lr->tanggal_mulai->translatedFormat('d M').' – '.$lr->tanggal_selesai->translatedFormat('d M Y').
                ' ('.$lr->jumlah_hari.' hari).',
            'url' => '/leave-requests/'.$lr->id,
            'icon' => 'bi-calendar2-heart',
        ];
    }
}
