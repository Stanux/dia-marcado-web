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
 * Notification sent to invite an existing user to join a wedding.
 * 
 * Includes a disclaimer if the user is already linked to another wedding,
 * informing them that they will be unlinked from the previous wedding.
 */
class ExistingUserInviteNotification extends Notification implements ShouldQueue
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
        $declineUrl = $this->getDeclineUrl();
        $expiresAt = $this->invite->expires_at->format('d/m/Y');

        $mail = (new MailMessage)
            ->subject('Você foi convidado(a) para planejar um casamento!')
            ->greeting("Olá, {$this->invite->name}!")
            ->line("{$this->inviter->name} convidou você para participar do planejamento do casamento no Dia Marcado.");

        // Add disclaimer if user is linked to another wedding
        if ($this->invite->previous_wedding_id) {
            $previousWedding = $this->invite->previousWedding;
            $previousWeddingTitle = $previousWedding?->title ?? 'outro casamento';
            
            $mail->line('')
                ->line('⚠️ **ATENÇÃO:** Você já está vinculado(a) ao projeto "' . $previousWeddingTitle . '".')
                ->line('Ao aceitar este convite, você será **desvinculado(a)** do casamento anterior e vinculado(a) a este novo projeto.')
                ->line('');
        }

        return $mail
            ->action('Aceitar Convite', $acceptUrl)
            ->line("Ou, se preferir, [clique aqui para recusar]({$declineUrl}).")
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
            'has_previous_wedding' => $this->invite->previous_wedding_id !== null,
            'previous_wedding_id' => $this->invite->previous_wedding_id,
        ];
    }

    /**
     * Get the URL for accepting the invitation.
     */
    private function getAcceptUrl(): string
    {
        return url("/convite/{$this->invite->token}/aceitar");
    }

    /**
     * Get the URL for declining the invitation.
     */
    private function getDeclineUrl(): string
    {
        return url("/convite/{$this->invite->token}/recusar");
    }
}
