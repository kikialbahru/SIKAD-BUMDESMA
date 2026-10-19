<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestReviewed extends Notification
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
        $approved = $lr->status === 'approved';

        $mail = (new MailMessage)
            ->subject('Pengajuan '.$jenis.' Anda '.($approved ? 'disetujui' : 'ditolak'))
            ->greeting('Halo '.($lr->employee->nama ?? 'Karyawan').',')
            ->line('Pengajuan '.$jenis.' Anda pada '.
                $lr->tanggal_mulai->translatedFormat('d M Y').' – '.$lr->tanggal_selesai->translatedFormat('d M Y').
                ' telah '.($approved ? 'disetujui' : 'ditolak').' oleh admin.');
        if (! $approved && $lr->alasan_penolakan) {
            $mail->line('Alasan penolakan: '.$lr->alasan_penolakan);
        }

        return $mail->action('Lihat pengajuan', route('leave.show', $lr->id));
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $jenis = $lr->jenis === 'sick' ? 'sakit' : ($lr->jenis === 'duty' ? 'dinas keluar' : 'cuti');
        $approved = $lr->status === 'approved';

        return [
            'title' => 'Pengajuan '.$jenis.' '.($approved ? 'disetujui' : 'ditolak'),
            'body' => 'Pengajuan '.$jenis.' '.$lr->tanggal_mulai->translatedFormat('d M').' – '.
                $lr->tanggal_selesai->translatedFormat('d M Y').' telah '.
                ($approved ? 'disetujui' : 'ditolak').' oleh admin.'.
                ($approved || ! $lr->alasan_penolakan ? '' : ' Alasan: '.$lr->alasan_penolakan),
            'url' => '/leave-requests/'.$lr->id,
            'icon' => $approved ? 'bi-check-circle' : 'bi-x-circle',
        ];
    }
}
