<?php

namespace App\Notifications;

use App\Models\RecurringBill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class BillDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private RecurringBill $bill)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reminder: Tagihan {$this->bill->name} Segera Jatuh Tempo")
            ->greeting("Halo {$notifiable->name},")
            ->line("Tagihan **{$this->bill->name}** akan jatuh tempo pada tanggal {$this->bill->due_day} bulan ini.")
            ->line("Jumlah: {$this->bill->currency} " . number_format($this->bill->amount, 2))
            ->action('Buka FinTrack', config('app.frontend_url', 'http://localhost:5173'))
            ->line('Jangan sampai telat bayar ya!');
    }
}