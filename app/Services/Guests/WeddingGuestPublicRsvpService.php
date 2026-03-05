<?php

namespace App\Services\Guests;

use App\Models\SiteLayout;
use App\Models\WeddingEvent;
use App\Models\WeddingEventRsvp;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WeddingGuestPublicRsvpService
{
    /**
     * @return array{
     *   event: array<string, mixed>,
     *   invite: array<string, mixed>,
     *   rules: array<string, mixed>,
     *   quota: array<string, mixed>,
     *   guests: array<int, array<string, mixed>>
     * }
     *
     * @throws RsvpSubmissionException
     */
    public function resolveByCode(string $siteSlug, string $eventId, string $rawCode): array
    {
        [$site, $event, $invite] = $this->resolveContext(
            siteSlug: $siteSlug,
            eventId: $eventId,
            rawCode: $rawCode,
            lockInvite: false,
        );

        $mode = $this->resolveMode($event, $invite);
        $quota = $this->buildQuotaPayload($event, $invite);
        $groupGuests = $this->loadInviteGroupGuests($invite, $event);
        $canAddByQuota = $this->canAddByQuota($quota);

        if (
            !$event->isClosed()
            && $invite->invite_type !== 'global'
            && $this->hasConfiguredQuota($quota)
            && !$canAddByQuota
        ) {
            throw new RsvpSubmissionException(
                'Este convite individual já atingiu 100% da cota.',
                409,
            );
        }

        $guests = [];
        if ($event->isClosed()) {
            if ($invite->primary_contact_id === null) {
                throw new RsvpSubmissionException(
                    'Este convite não possui contato principal configurado para evento fechado.',
                    422,
                );
            }

            $guests = $groupGuests;

            if (!$this->hasPendingGuests($guests)) {
                throw new RsvpSubmissionException(
                    'Este convite não possui mais confirmações pendentes.',
                    409,
                );
            }
        }

        $alreadySubmitted = false;
        $allowAdd = !$event->isClosed() && (
            $invite->invite_type === 'global'
                ? $canAddByQuota
                : true
        );
        $blockedReason = !$allowAdd
            ? 'quota_reached'
            : null;

        return [
            'event' => [
                'id' => (string) $event->id,
                'name' => (string) $event->name,
                'type' => (string) $event->event_type,
                'mode' => $mode,
            ],
            'invite' => [
                'id' => (string) $invite->id,
                'type' => (string) $invite->invite_type,
                'code' => (string) $invite->confirmation_code,
                'already_submitted' => $alreadySubmitted,
                'primary_contact_id' => $invite->primary_contact_id ? (string) $invite->primary_contact_id : null,
            ],
            'rules' => [
                'allow_add' => $allowAdd,
                'allow_delete' => $allowAdd,
                'allow_status_edit' => $event->isClosed(),
                'email_required_for_primary' => true,
                'single_event_selection' => true,
                'blocked_reason' => $blockedReason,
            ],
            'quota' => $quota,
            'guests' => $guests,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws RsvpSubmissionException
     */
    public function submitByCode(string $siteSlug, string $eventId, string $rawCode, array $payload): array
    {
        return DB::transaction(function () use ($siteSlug, $eventId, $rawCode, $payload): array {
            [, $event, $invite] = $this->resolveContext(
                siteSlug: $siteSlug,
                eventId: $eventId,
                rawCode: $rawCode,
                lockInvite: true,
            );

            if ($event->isClosed()) {
                return $this->submitClosedEvent($event, $invite, $payload);
            }

            return $this->submitOpenEvent($event, $invite, $payload);
        });
    }

    /**
     * @return array{SiteLayout, WeddingEvent, WeddingInvite}
     *
     * @throws RsvpSubmissionException
     */
    private function resolveContext(string $siteSlug, string $eventId, string $rawCode, bool $lockInvite): array
    {
        $site = SiteLayout::withoutGlobalScopes()
            ->where('slug', $siteSlug)
            ->first();

        if (!$site) {
            throw new RsvpSubmissionException('Site não encontrado.', 404);
        }

        $event = WeddingEvent::withoutGlobalScopes()
            ->whereKey($eventId)
            ->where('wedding_id', $site->wedding_id)
            ->first();

        if (!$event || !$event->is_active) {
            throw new RsvpSubmissionException('Evento indisponível para confirmação.', 422);
        }

        $code = $this->normalizeCode($rawCode);
        if ($code === '') {
            throw new RsvpSubmissionException('Informe o código de confirmação.', 422);
        }

        $inviteQuery = WeddingInvite::withoutGlobalScopes()
            ->where('wedding_id', $site->wedding_id)
            ->where('event_id', $event->id)
            ->whereRaw('UPPER(confirmation_code) = ?', [$code])
            ->where('is_active', true);

        if ($lockInvite) {
            $inviteQuery->lockForUpdate();
        }

        $invite = $inviteQuery->first();

        if (!$invite) {
            throw new RsvpSubmissionException('Código inválido para o evento selecionado.', 404);
        }

        if ($invite->isExpired()) {
            throw new RsvpSubmissionException('Este convite expirou.', 410);
        }

        return [$site, $event, $invite];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws RsvpSubmissionException
     */
    private function submitOpenEvent(WeddingEvent $event, WeddingInvite $invite, array $payload): array
    {
        $quota = $this->buildQuotaPayload($event, $invite);
        $canAddByQuota = $this->canAddByQuota($quota);

        if (
            $invite->invite_type !== 'global'
            && $this->hasConfiguredQuota($quota)
            && !$canAddByQuota
        ) {
            throw new RsvpSubmissionException(
                'Este convite individual já atingiu 100% da cota.',
                409,
            );
        }

        if (
            $invite->invite_type === 'global'
            && $this->hasConfiguredQuota($quota)
            && !$canAddByQuota
        ) {
            throw new RsvpSubmissionException(
                'Este convite global atingiu o limite de cota.',
                409,
            );
        }

        $rows = $this->normalizeOpenRows($payload['guests'] ?? null);

        if ($rows === []) {
            throw new RsvpSubmissionException('Adicione pelo menos um convidado antes de salvar.', 422);
        }

        $primaryIndex = $this->resolvePrimaryRowIndex($rows);
        if ($primaryIndex !== null && empty($rows[$primaryIndex]['email'])) {
            throw new RsvpSubmissionException('O e-mail do contato principal é obrigatório.', 422);
        }

        $existingGuestsByRow = $this->resolveExistingGuestsForOpenRows(
            weddingId: (string) $invite->wedding_id,
            rows: $rows,
            primaryIndex: $primaryIndex,
        );

        [$newAdults, $newChildren] = $this->calculateOpenSubmissionQuotaUsage(
            weddingId: (string) $invite->wedding_id,
            eventId: (string) $event->id,
            inviteId: (string) $invite->id,
            rows: $rows,
            existingGuestsByRow: $existingGuestsByRow,
        );

        if ($quota['adult_limit'] !== null && ($quota['adult_used'] + $newAdults) > $quota['adult_limit']) {
            throw new RsvpSubmissionException('A cota de adultos deste convite foi excedida.', 422);
        }

        if ($quota['child_limit'] !== null && ($quota['child_used'] + $newChildren) > $quota['child_limit']) {
            throw new RsvpSubmissionException('A cota de crianças deste convite foi excedida.', 422);
        }

        $createdGuests = 0;
        $updatedRsvps = 0;

        if ($primaryIndex !== null) {
            $primaryRow = $rows[$primaryIndex];
            $primaryStatus = WeddingEventRsvp::STATUS_CONFIRMED;
            $existingPrimaryGuest = $existingGuestsByRow[$primaryIndex] ?? null;
            $primaryGuest = $existingPrimaryGuest
                ?? $this->createGuestFromRow(
                    weddingId: (string) $invite->wedding_id,
                    row: $primaryRow,
                    primaryContactId: null,
                );

            $this->saveRsvp(
                weddingId: (string) $invite->wedding_id,
                eventId: (string) $event->id,
                inviteId: (string) $invite->id,
                guestId: (string) $primaryGuest->id,
                status: $primaryStatus,
            );

            $createdGuests += $existingPrimaryGuest ? 0 : 1;
            $updatedRsvps++;

            foreach ($rows as $index => $row) {
                if ($index === $primaryIndex) {
                    continue;
                }

                $existingGuest = $existingGuestsByRow[$index] ?? null;
                $guest = $existingGuest
                    ?? $this->createGuestFromRow(
                        weddingId: (string) $invite->wedding_id,
                        row: $row,
                        primaryContactId: (string) $primaryGuest->id,
                    );

                $guestStatus = WeddingEventRsvp::STATUS_CONFIRMED;

                $this->saveRsvp(
                    weddingId: (string) $invite->wedding_id,
                    eventId: (string) $event->id,
                    inviteId: (string) $invite->id,
                    guestId: (string) $guest->id,
                    status: $guestStatus,
                );

                if (!$existingGuest) {
                    $createdGuests++;
                }

                $updatedRsvps++;
            }

            if ($invite->invite_type !== 'global') {
                $invite->primary_contact_id = $primaryGuest->id;
                $invite->save();
            }
        } else {
            foreach ($rows as $index => $row) {
                $existingGuest = $existingGuestsByRow[$index] ?? null;
                $guest = $existingGuest
                    ?? $this->createGuestFromRow(
                        weddingId: (string) $invite->wedding_id,
                        row: $row,
                        primaryContactId: null,
                    );

                $this->saveRsvp(
                    weddingId: (string) $invite->wedding_id,
                    eventId: (string) $event->id,
                    inviteId: (string) $invite->id,
                    guestId: (string) $guest->id,
                    status: WeddingEventRsvp::STATUS_CONFIRMED,
                );

                if (!$existingGuest) {
                    $createdGuests++;
                }

                $updatedRsvps++;
            }
        }

        return [
            'mode' => $this->resolveMode($event, $invite),
            'created_guests' => $createdGuests,
            'updated_rsvps' => $updatedRsvps,
            'message' => 'Lista salva com sucesso.',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws RsvpSubmissionException
     */
    private function submitClosedEvent(WeddingEvent $event, WeddingInvite $invite, array $payload): array
    {
        if ($invite->primary_contact_id === null) {
            throw new RsvpSubmissionException(
                'Este convite não possui contato principal configurado para evento fechado.',
                422,
            );
        }

        $groupGuests = $this->loadInviteGroupGuests($invite, $event);
        if (!$this->hasPendingGuests($groupGuests)) {
            throw new RsvpSubmissionException(
                'Este convite não possui mais confirmações pendentes.',
                409,
            );
        }

        $responses = $this->normalizeClosedResponses($payload['responses'] ?? null);
        if ($responses === []) {
            return [
                'mode' => $this->resolveMode($event, $invite),
                'updated_rsvps' => 0,
                'message' => 'Nenhuma alteração foi enviada.',
            ];
        }

        $allowedGuests = collect($groupGuests)
            ->keyBy('id');

        $existingRsvps = WeddingEventRsvp::withoutGlobalScopes()
            ->where('wedding_id', $invite->wedding_id)
            ->where('event_id', $event->id)
            ->whereIn('guest_id', $allowedGuests->keys()->all())
            ->get()
            ->keyBy('guest_id');

        $updated = 0;

        foreach ($responses as $response) {
            $guestId = (string) $response['guest_id'];
            $status = (string) $response['status'];

            if (!$allowedGuests->has($guestId)) {
                throw new RsvpSubmissionException('Há convidados fora do escopo permitido para este convite.', 422);
            }

            /** @var WeddingEventRsvp|null $current */
            $current = $existingRsvps->get($guestId);
            if ($current && $current->status === $status) {
                continue;
            }

            $this->saveRsvp(
                weddingId: (string) $invite->wedding_id,
                eventId: (string) $event->id,
                inviteId: (string) $invite->id,
                guestId: $guestId,
                status: $status,
            );

            $updated++;
        }

        return [
            'mode' => $this->resolveMode($event, $invite),
            'updated_rsvps' => $updated,
            'message' => $updated > 0
                ? 'Confirmações atualizadas com sucesso.'
                : 'Nenhuma alteração foi necessária.',
        ];
    }

    /**
     * @return array<int, array{
     *   id: string,
     *   name: string,
     *   email: string|null,
     *   phone: string|null,
     *   side: string,
     *   is_child: bool,
     *   is_primary_contact: bool,
     *   status: string
     * }>
     */
    private function loadInviteGroupGuests(WeddingInvite $invite, WeddingEvent $event): array
    {
        if ($invite->primary_contact_id === null) {
            return [];
        }

        $guests = WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $invite->wedding_id)
            ->where(function ($query) use ($invite): void {
                $query->where('id', $invite->primary_contact_id)
                    ->orWhere('primary_contact_id', $invite->primary_contact_id);
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$invite->primary_contact_id])
            ->orderBy('name')
            ->get();

        if ($guests->isEmpty()) {
            return [];
        }

        $rsvps = WeddingEventRsvp::withoutGlobalScopes()
            ->where('wedding_id', $invite->wedding_id)
            ->where('event_id', $event->id)
            ->whereIn('guest_id', $guests->pluck('id')->all())
            ->get()
            ->keyBy('guest_id');

        return $guests
            ->map(function (WeddingGuest $guest) use ($invite, $rsvps): array {
                /** @var WeddingEventRsvp|null $rsvp */
                $rsvp = $rsvps->get($guest->id);

                return [
                    'id' => (string) $guest->id,
                    'name' => (string) $guest->name,
                    'email' => $guest->email ? (string) $guest->email : null,
                    'phone' => $guest->phone ? (string) $guest->phone : null,
                    'side' => (string) ($guest->side ?: 'both'),
                    'is_child' => (bool) $guest->is_child,
                    'is_primary_contact' => (string) $guest->id === (string) $invite->primary_contact_id,
                    'status' => $this->normalizeStatus($rsvp?->status),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   has_quota: bool,
     *   adult_limit: int|null,
     *   child_limit: int|null,
     *   adult_used: int,
     *   child_used: int,
     *   adult_remaining: int|null,
     *   child_remaining: int|null
     * }
     */
    private function buildQuotaPayload(WeddingEvent $event, WeddingInvite $invite): array
    {
        $adultLimit = $invite->adult_quota !== null ? (int) $invite->adult_quota : null;
        $childLimit = $invite->child_quota !== null ? (int) $invite->child_quota : null;

        $usageRow = WeddingEventRsvp::withoutGlobalScopes()
            ->from('wedding_event_rsvps as rsvps')
            ->join('wedding_guests as guests', 'guests.id', '=', 'rsvps.guest_id')
            ->where('rsvps.wedding_id', $invite->wedding_id)
            ->where('rsvps.event_id', $event->id)
            ->where('rsvps.invite_id', $invite->id)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN guests.is_child = true THEN 1 ELSE 0 END), 0) as child_used,
                COALESCE(SUM(CASE WHEN guests.is_child = true THEN 0 ELSE 1 END), 0) as adult_used
            ')
            ->first();

        $adultUsed = (int) ($usageRow?->adult_used ?? 0);
        $childUsed = (int) ($usageRow?->child_used ?? 0);

        return [
            'has_quota' => $adultLimit !== null || $childLimit !== null,
            'adult_limit' => $adultLimit,
            'child_limit' => $childLimit,
            'adult_used' => $adultUsed,
            'child_used' => $childUsed,
            'adult_remaining' => $adultLimit === null ? null : max($adultLimit - $adultUsed, 0),
            'child_remaining' => $childLimit === null ? null : max($childLimit - $childUsed, 0),
        ];
    }

    /**
     * @param array<int, array{name:string,email:string|null,phone:string|null,side:string,is_child:bool,is_primary_contact:bool}> $rows
     * @return array<int, WeddingGuest|null>
     *
     * @throws RsvpSubmissionException
     */
    private function resolveExistingGuestsForOpenRows(string $weddingId, array $rows, ?int $primaryIndex): array
    {
        $existingGuestsByRow = [];
        $seenExistingIds = [];
        $seenNewRowKeys = [];
        $linkedWithoutPrimaryRows = [];

        foreach ($rows as $index => $row) {
            $existingGuest = $this->findExistingGuestByIdentity($weddingId, $row);

            if ($existingGuest) {
                $existingGuestId = (string) $existingGuest->id;

                if ($primaryIndex === null && $existingGuest->primary_contact_id !== null) {
                    $linkedWithoutPrimaryRows[] = $index;
                    $existingGuestsByRow[$index] = $existingGuest;
                    continue;
                }

                if (isset($seenExistingIds[$existingGuestId])) {
                    throw new RsvpSubmissionException(
                        'Linha ' . ($index + 1) . ': convidado já informado nesta lista.',
                        422,
                    );
                }

                if (
                    (
                        $primaryIndex !== null
                        && $index === $primaryIndex
                    )
                    && $existingGuest->primary_contact_id !== null
                ) {
                    throw new RsvpSubmissionException(
                        'O contato definido como Principal já está cadastrado como convidado.',
                        422,
                    );
                }

                $seenExistingIds[$existingGuestId] = true;
                $existingGuestsByRow[$index] = $existingGuest;
                continue;
            }

            $rowKey = $this->buildOpenRowIdentityKey($row);
            if (isset($seenNewRowKeys[$rowKey])) {
                throw new RsvpSubmissionException(
                    'Linha ' . ($index + 1) . ': convidado duplicado na mesma lista.',
                    422,
                );
            }

            $seenNewRowKeys[$rowKey] = true;
            $existingGuestsByRow[$index] = null;
        }

        if ($linkedWithoutPrimaryRows !== []) {
            throw new RsvpSubmissionException(
                'O convidado já está vinculado a um contato principal e não pode ser adicionado sem vínculo.',
                422,
                [
                    'highlight_rows' => array_values(array_unique($linkedWithoutPrimaryRows)),
                ],
            );
        }

        return $existingGuestsByRow;
    }

    /**
     * @param array<int, array{name:string,email:string|null,phone:string|null,side:string,is_child:bool,is_primary_contact:bool}> $rows
     * @param array<int, WeddingGuest|null> $existingGuestsByRow
     * @return array{int, int}
     */
    private function calculateOpenSubmissionQuotaUsage(
        string $weddingId,
        string $eventId,
        string $inviteId,
        array $rows,
        array $existingGuestsByRow,
    ): array {
        $existingGuestIds = collect($existingGuestsByRow)
            ->filter()
            ->map(fn (WeddingGuest $guest): string => (string) $guest->id)
            ->values();

        $alreadyLinkedGuestIds = [];

        if ($existingGuestIds->isNotEmpty()) {
            $alreadyLinkedGuestIds = WeddingEventRsvp::withoutGlobalScopes()
                ->where('wedding_id', $weddingId)
                ->where('event_id', $eventId)
                ->where('invite_id', $inviteId)
                ->whereIn('guest_id', $existingGuestIds->all())
                ->pluck('guest_id')
                ->mapWithKeys(fn ($id): array => [(string) $id => true])
                ->all();
        }

        $newAdults = 0;
        $newChildren = 0;

        foreach ($rows as $index => $row) {
            $existingGuest = $existingGuestsByRow[$index] ?? null;

            if ($existingGuest && isset($alreadyLinkedGuestIds[(string) $existingGuest->id])) {
                continue;
            }

            $isChild = $existingGuest
                ? (bool) $existingGuest->is_child
                : (bool) $row['is_child'];

            if ($isChild) {
                $newChildren++;
            } else {
                $newAdults++;
            }
        }

        return [$newAdults, $newChildren];
    }

    /**
     * @param array{name:string,email:string|null,phone:string|null,side:string,is_child:bool,is_primary_contact:bool} $row
     *
     * @throws RsvpSubmissionException
     */
    private function findExistingGuestByIdentity(string $weddingId, array $row): ?WeddingGuest
    {
        $normalizedName = $this->normalizeGuestName($row['name']);
        $normalizedEmail = $this->normalizeGuestEmail($row['email'] ?? null);
        $normalizedPhone = $this->normalizeGuestPhone($row['phone'] ?? null);

        $query = WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $weddingId);

        if ($normalizedEmail !== null || $normalizedPhone !== null) {
            $query->where(function ($subQuery) use ($normalizedEmail, $normalizedPhone): void {
                if ($normalizedEmail !== null) {
                    $subQuery->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);
                }

                if ($normalizedPhone !== null) {
                    $subQuery->orWhereNotNull('phone');
                }
            });
        } else {
            $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $row['name']))]);
        }

        $matches = $query
            ->get(['id', 'name', 'email', 'phone', 'primary_contact_id', 'is_child'])
            ->filter(function (WeddingGuest $guest) use ($normalizedName, $normalizedEmail, $normalizedPhone): bool {
                $guestName = $this->normalizeGuestName((string) $guest->name);
                if ($guestName !== $normalizedName) {
                    return false;
                }

                if ($normalizedEmail === null && $normalizedPhone === null) {
                    return true;
                }

                $emailMatches = $normalizedEmail !== null
                    && $this->normalizeGuestEmail($guest->email) === $normalizedEmail;
                $phoneMatches = $normalizedPhone !== null
                    && $this->normalizeGuestPhone($guest->phone) === $normalizedPhone;

                return $emailMatches || $phoneMatches;
            })
            ->values();

        if ($matches->count() > 1) {
            throw new RsvpSubmissionException(
                'Há mais de um convidado com os mesmos dados na lista. Peça ajuste ao casal organizador.',
                422,
            );
        }

        return $matches->first();
    }

    /**
     * @param array{name:string,email:string|null,phone:string|null,side:string,is_child:bool,is_primary_contact:bool} $row
     */
    private function buildOpenRowIdentityKey(array $row): string
    {
        return implode('|', [
            $this->normalizeGuestName($row['name']),
            $this->normalizeGuestEmail($row['email'] ?? null) ?? '__null__',
            $this->normalizeGuestPhone($row['phone'] ?? null) ?? '__null__',
        ]);
    }

    private function normalizeGuestName(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ');
    }

    private function normalizeGuestEmail(mixed $value): ?string
    {
        $email = trim((string) $value);

        if ($email === '') {
            return null;
        }

        return Str::lower($email);
    }

    private function normalizeGuestPhone(mixed $value): ?string
    {
        $phone = trim((string) $value);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        return $digits;
    }

    /**
     * @param mixed $rows
     * @return array<int, array{
     *   name: string,
     *   email: string|null,
     *   phone: string|null,
     *   side: string,
     *   is_child: bool,
     *   is_primary_contact: bool
     * }>
     *
     * @throws RsvpSubmissionException
     */
    private function normalizeOpenRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row)) {
                throw new RsvpSubmissionException('Formato inválido na lista de convidados.', 422);
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                throw new RsvpSubmissionException('Linha ' . ($index + 1) . ': informe o nome do convidado.', 422);
            }

            $email = isset($row['email']) && trim((string) $row['email']) !== ''
                ? mb_strtolower(trim((string) $row['email']))
                : null;
            $phone = isset($row['phone']) && trim((string) $row['phone']) !== ''
                ? trim((string) $row['phone'])
                : null;

            $side = in_array(($row['side'] ?? 'both'), ['bride', 'groom', 'both'], true)
                ? (string) $row['side']
                : 'both';

            $isChild = (bool) ($row['is_child'] ?? false);
            $isPrimaryContact = (bool) ($row['is_primary_contact'] ?? false);

            if ($isPrimaryContact && $email === null) {
                throw new RsvpSubmissionException(
                    'Linha ' . ($index + 1) . ': o e-mail é obrigatório para o contato principal.',
                    422,
                );
            }

            $normalized[] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'side' => $side,
                'is_child' => $isChild,
                'is_primary_contact' => $isPrimaryContact,
            ];
        }

        return $normalized;
    }

    /**
     * @param mixed $responses
     * @return array<int, array{guest_id: string, status: string}>
     *
     * @throws RsvpSubmissionException
     */
    private function normalizeClosedResponses(mixed $responses): array
    {
        if (!is_array($responses)) {
            return [];
        }

        $indexed = [];

        foreach (array_values($responses) as $index => $response) {
            if (!is_array($response)) {
                throw new RsvpSubmissionException('Formato inválido nas respostas do evento fechado.', 422);
            }

            $guestId = trim((string) ($response['guest_id'] ?? ''));
            if ($guestId === '') {
                throw new RsvpSubmissionException('Linha ' . ($index + 1) . ': convidado inválido.', 422);
            }

            $status = $this->normalizeStatus($response['status'] ?? null);
            if (!in_array($status, [WeddingEventRsvp::STATUS_CONFIRMED, WeddingEventRsvp::STATUS_DECLINED], true)) {
                throw new RsvpSubmissionException(
                    'Linha ' . ($index + 1) . ': status inválido. Use Confirmar ou Recusar.',
                    422,
                );
            }

            $indexed[$guestId] = [
                'guest_id' => $guestId,
                'status' => $status,
            ];
        }

        return array_values($indexed);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolvePrimaryRowIndex(array &$rows): ?int
    {
        $primaryIndex = null;

        foreach ($rows as $index => $row) {
            if (!empty($row['is_primary_contact'])) {
                $primaryIndex = $index;
            }
        }

        if ($primaryIndex === null) {
            return null;
        }

        foreach ($rows as $index => $row) {
            $rows[$index]['is_primary_contact'] = $index === $primaryIndex;
        }

        return $primaryIndex;
    }

    /**
     * @param array{name:string,email:string|null,phone:string|null,side:string,is_child:bool} $row
     */
    private function createGuestFromRow(string $weddingId, array $row, ?string $primaryContactId): WeddingGuest
    {
        return WeddingGuest::create([
            'wedding_id' => $weddingId,
            'created_by' => null,
            'primary_contact_id' => $primaryContactId,
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'side' => $row['side'],
            'status' => WeddingEventRsvp::STATUS_PENDING,
            'is_child' => $row['is_child'],
            'is_active' => true,
        ]);
    }

    private function saveRsvp(string $weddingId, string $eventId, string $inviteId, string $guestId, string $status): void
    {
        WeddingEventRsvp::withoutGlobalScopes()->updateOrCreate(
            [
                'event_id' => $eventId,
                'guest_id' => $guestId,
            ],
            [
                'wedding_id' => $weddingId,
                'invite_id' => $inviteId,
                'status' => $status,
                'responded_at' => now(),
                'response_channel' => 'public_site',
            ],
        );
    }

    private function normalizeCode(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/\s+/', '', $normalized) ?: '';

        return $normalized;
    }

    private function resolveMode(WeddingEvent $event, WeddingInvite $invite): string
    {
        if ($event->isClosed()) {
            return 'closed';
        }

        return ($invite->adult_quota !== null || $invite->child_quota !== null)
            ? 'open_with_quota'
            : 'open_without_quota';
    }

    private function normalizeStatus(mixed $status): string
    {
        $value = trim((string) $status);

        return match ($value) {
            WeddingEventRsvp::STATUS_CONFIRMED => WeddingEventRsvp::STATUS_CONFIRMED,
            WeddingEventRsvp::STATUS_DECLINED => WeddingEventRsvp::STATUS_DECLINED,
            default => WeddingEventRsvp::STATUS_PENDING,
        };
    }

    /**
     * @param array{
     *   adult_limit:int|null,
     *   child_limit:int|null,
     *   adult_remaining:int|null,
     *   child_remaining:int|null
     * } $quota
     */
    private function hasConfiguredQuota(array $quota): bool
    {
        return $quota['adult_limit'] !== null || $quota['child_limit'] !== null;
    }

    /**
     * @param array{
     *   adult_limit:int|null,
     *   child_limit:int|null,
     *   adult_remaining:int|null,
     *   child_remaining:int|null
     * } $quota
     */
    private function canAddByQuota(array $quota): bool
    {
        if (!$this->hasConfiguredQuota($quota)) {
            return true;
        }

        $adultHasSpace = $quota['adult_remaining'] === null || $quota['adult_remaining'] > 0;
        $childHasSpace = $quota['child_remaining'] === null || $quota['child_remaining'] > 0;

        return $adultHasSpace || $childHasSpace;
    }

    /**
     * @param array<int, array{status:string}> $guests
     */
    private function hasPendingGuests(array $guests): bool
    {
        return collect($guests)
            ->contains(
                fn (array $guest): bool => ($guest['status'] ?? WeddingEventRsvp::STATUS_PENDING) === WeddingEventRsvp::STATUS_PENDING
            );
    }
}
