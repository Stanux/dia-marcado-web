<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestInvite;
use App\Models\GuestMessage;
use App\Models\GuestMessageLog;
use App\Models\SystemConfig;
use App\Notifications\GuestInviteNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class GuestInviteNotificationService
{
    public function send(GuestInvite $invite): array
    {
        $household = $invite->household;
        $wedding = $household?->wedding;

        if (!$household || !$wedding) {
            return ['ok' => false, 'message' => 'Convite sem núcleo ou casamento válido.'];
        }

        $channel = $invite->channel ?: 'email';
        $recipient = $this->resolveRecipient($invite, $channel);
        if (!$recipient) {
            return ['ok' => false, 'message' => 'Nenhum contato válido encontrado para este canal.'];
        }

        $link = $this->buildInviteLink($invite);
        $messageText = $this->buildMessageText($invite, $link, $recipient['name'] ?? null);

        $message = GuestMessage::create([
            'wedding_id' => $wedding->id,
            'created_by' => $invite->created_by,
            'channel' => $channel,
            'subject' => $channel === 'email' ? 'Convite para RSVP' : null,
            'body' => $messageText,
            'payload' => [
                'invite_id' => $invite->id,
                'link' => $link,
                'recipient' => $recipient,
            ],
            'status' => 'sending',
        ]);

        $log = GuestMessageLog::create([
            'message_id' => $message->id,
            'guest_id' => $recipient['guest_id'] ?? null,
            'status' => 'sending',
            'occurred_at' => now(),
            'metadata' => [
                'channel' => $channel,
                'contact' => $recipient['contact'] ?? null,
            ],
        ]);

        try {
            if ($channel === 'email') {
                Notification::route('mail', $recipient['contact'])
                    ->notify(new GuestInviteNotification($invite, $link, $recipient['name'] ?? null));
            } elseif ($channel === 'whatsapp') {
                $this->sendViaWebhook('guests.whatsapp_webhook_url', $recipient['contact'], $messageText, $invite, $link);
            } elseif ($channel === 'sms') {
                $this->sendViaWebhook('guests.sms_webhook_url', $recipient['contact'], $messageText, $invite, $link);
            } else {
                throw new \RuntimeException('Canal inválido.');
            }

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            $log->update([
                'status' => 'sent',
                'occurred_at' => now(),
            ]);

            return ['ok' => true, 'message' => 'Convite enviado com sucesso.'];
        } catch (\Throwable $e) {
            $message->update([
                'status' => 'failed',
            ]);
            $log->update([
                'status' => 'failed',
                'occurred_at' => now(),
                'metadata' => array_merge($log->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);

            report($e);

            return ['ok' => false, 'message' => 'Falha ao enviar convite: ' . $e->getMessage()];
        }
    }

    private function resolveRecipient(GuestInvite $invite, string $channel): ?array
    {
        $guest = $invite->guest;
        if (!$guest) {
            $guest = $invite->household?->guests()
                ->orderBy('is_child')
                ->orderBy('created_at')
                ->first();
        }

        if (!$guest instanceof Guest) {
            return null;
        }

        if ($channel === 'email') {
            $email = $guest->email ?: $invite->household?->guests()->whereNotNull('email')->value('email');
            if (!$email) {
                return null;
            }

            return [
                'guest_id' => $guest->id,
                'name' => $guest->name,
                'contact' => $email,
            ];
        }

        $phone = $guest->phone ?: $invite->household?->guests()->whereNotNull('phone')->value('phone');
        if (!$phone) {
            return null;
        }

        return [
            'guest_id' => $guest->id,
            'name' => $guest->name,
            'contact' => $phone,
        ];
    }

    private function buildInviteLink(GuestInvite $invite): string
    {
        $siteSlug = $invite->household?->wedding?->siteLayout?->slug;
        if ($siteSlug) {
            return url('/site/' . $siteSlug . '?token=' . $invite->token);
        }

        return url('/site?token=' . $invite->token);
    }

    private function buildMessageText(GuestInvite $invite, string $link, ?string $name): string
    {
        $weddingTitle = $invite->household?->wedding?->title ?? 'nosso casamento';
        $recipient = $name ? "{$name}, " : '';
        $expires = $invite->expires_at ? ' (expira em ' . $invite->expires_at->format('d/m/Y') . ')' : '';

        return "{$recipient}você foi convidado(a) para confirmar presença no casamento: {$weddingTitle}. Acesse: {$link}{$expires}";
    }

    private function sendViaWebhook(string $configKey, string $to, string $message, GuestInvite $invite, string $link): void
    {
        $url = SystemConfig::get($configKey);
        if (!$url) {
            throw new \RuntimeException('Webhook do canal não configurado.');
        }

        $payload = [
            'to' => $to,
            'message' => $message,
            'invite_id' => $invite->id,
            'wedding_id' => $invite->household?->wedding_id,
            'token' => $invite->token,
            'link' => $link,
            'channel' => $invite->channel,
            'request_id' => (string) Str::uuid(),
        ];

        $response = Http::timeout(10)->post($url, $payload);
        if (!$response->successful()) {
            throw new \RuntimeException('Webhook retornou status ' . $response->status());
        }
    }
}
