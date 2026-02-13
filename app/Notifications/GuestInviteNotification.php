<?php

namespace App\Notifications;

use App\Models\GuestInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly GuestInvite $invite,
        private readonly string $link,
        private readonly ?string $recipientName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $weddingTitle = $this->invite->household?->wedding?->title ?? 'nosso casamento';
        $greeting = $this->recipientName ? "Olá, {$this->recipientName}!" : 'Olá!';

        $mail = (new MailMessage)
            ->greeting($greeting)
            ->subject('Convite para RSVP')
            ->line("Você foi convidado(a) para confirmar presença no casamento: {$weddingTitle}.")
            ->action('Confirmar presença', $this->link);

        if ($this->invite->expires_at) {
            $mail->line('Este convite expira em ' . $this->invite->expires_at->format('d/m/Y') . '.');
        }

        $mail->line('Se você tiver dúvidas, responda a este e-mail.');

        return $mail;
    }
}
