<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PartnerInvite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to invite a new user (who doesn't have an account) to join a wedding.
 */
class NewUserInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param PartnerInvite $invite The invitation
     * @param User $inviter The user who sent the invitation
     */
    public function __construct(
        public readonly PartnerInvite $invite,
        public readonly User $inviter
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = $this->getAcceptUrl();
        $expiresAt = $this->invite->expires_at->format('d/m/Y');

        return (new MailMessage)
            ->subject('Você foi convidado(a) para planejar um casamento!')
            ->greeting("Olá, {$this->invite->name}!")
            ->line("{$this->inviter->name} convidou você para participar do planejamento do casamento no Dia Marcado.")
            ->line('Clique no botão abaixo para criar sua conta e aceitar o convite.')
            ->action('Aceitar Convite', $acceptUrl)
            ->line("Este convite expira em {$expiresAt}.")
            ->salutation('Atenciosamente, Equipe Dia Marcado');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invite_id' => $this->invite->id,
            'wedding_id' => $this->invite->wedding_id,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
        ];
    }

    /**
     * Get the URL for accepting the invitation.
     */
    private function getAcceptUrl(): string
    {
        return url("/convite/{$this->invite->token}");
    }
}
