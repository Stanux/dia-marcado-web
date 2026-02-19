<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\PlaceholderServiceInterface;
use App\Models\PartnerInvite;
use App\Models\Wedding;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for replacing placeholders in site content with wedding data.
 * 
 * Handles couple member names, dates, venue information, and location data.
 */
class PlaceholderService implements PlaceholderServiceInterface
{
    /**
     * Portuguese month names for date formatting.
     */
    private const MONTH_NAMES = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    /**
     * {@inheritdoc}
     */
    public function replacePlaceholders(string $content, Wedding $wedding): string
    {
        $replacements = $this->buildReplacements($wedding);

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceInArray(array $content, Wedding $wedding): array
    {
        $result = [];

        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->replacePlaceholders($value, $wedding);
            } elseif (is_array($value)) {
                $result[$key] = $this->replaceInArray($value, $wedding);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePlaceholders(): array
    {
        return [
            '{nome_1}' => 'Nome do primeiro membro do casal',
            '{nome_2}' => 'Nome do segundo membro do casal',
            '{noivo}' => 'Nome do primeiro membro do casal',
            '{noiva}' => 'Nome do segundo membro do casal',
            '{primeiro_nome_1}' => 'Primeiro nome do primeiro membro do casal',
            '{primeiro_nome_2}' => 'Primeiro nome do segundo membro do casal',
            '{primeiro_nome_noivo}' => 'Primeiro nome do primeiro membro do casal',
            '{primeiro_nome_noiva}' => 'Primeiro nome do segundo membro do casal',
            '{noivos}' => 'Todos os nomes do casal separados por " e "',
            '{data_extenso}' => 'Data do casamento em formato extenso (ex: "15 de Março de 2025")',
            '{data_simples}' => 'Data do casamento em formato simples (ex: "15/03/2025")',
            '{data}' => 'Data do casamento formatada (ex: "15 de Março de 2025")',
            '{data_curta}' => 'Data do casamento curta (ex: "15/03/2025")',
            '{local}' => 'Nome do local do evento',
            '{cidade}' => 'Cidade do evento',
            '{estado}' => 'Estado do evento',
            '{cidade_estado}' => 'Cidade e estado (ex: "São Paulo - SP")',
        ];
    }

    /**
     * Build the replacement map for all placeholders.
     *
     * @param Wedding $wedding The wedding to get data from
     * @return array<string, string> Map of placeholder => replacement value
     */
    private function buildReplacements(Wedding $wedding): array
    {
        $coupleNames = $this->getCoupleNames($wedding);

        // If the partner is not linked yet, use the latest valid pending invite name
        // so placeholders can already render the noiva name after onboarding/settings.
        if ($coupleNames->count() < 2) {
            $pendingPartnerName = $this->getPendingPartnerInviteName($wedding);

            if ($pendingPartnerName !== '') {
                $coupleNames->push($pendingPartnerName);
            }
        }
        
        return [
            '{nome_1}' => $this->getNoivo($coupleNames),
            '{nome_2}' => $this->getNoiva($coupleNames),
            '{noivo}' => $this->getNoivo($coupleNames),
            '{noiva}' => $this->getNoiva($coupleNames),
            '{primeiro_nome_1}' => $this->getFirstName($this->getNoivo($coupleNames)),
            '{primeiro_nome_2}' => $this->getFirstName($this->getNoiva($coupleNames)),
            '{primeiro_nome_noivo}' => $this->getFirstName($this->getNoivo($coupleNames)),
            '{primeiro_nome_noiva}' => $this->getFirstName($this->getNoiva($coupleNames)),
            '{noivos}' => $this->getNoivos($coupleNames),
            '{data_extenso}' => $this->formatDateLong($wedding->wedding_date),
            '{data_simples}' => $this->formatDateShort($wedding->wedding_date),
            '{data}' => $this->formatDateLong($wedding->wedding_date),
            '{data_curta}' => $this->formatDateShort($wedding->wedding_date),
            '{local}' => $wedding->venue ?? '',
            '{cidade}' => $wedding->city ?? '',
            '{estado}' => $wedding->state ?? '',
            '{cidade_estado}' => $this->formatCidadeEstado($wedding->city, $wedding->state),
        ];
    }

    /**
     * Get pending partner name from the latest valid invite.
     */
    private function getPendingPartnerInviteName(Wedding $wedding): string
    {
        $name = PartnerInvite::query()
            ->where('wedding_id', $wedding->id)
            ->valid()
            ->latest('created_at')
            ->value('name');

        if (!is_string($name)) {
            return '';
        }

        return trim($name);
    }

    /**
     * Get the first name from a full name.
     * Extracts the first word from the name.
     *
     * @param string $fullName The full name
     * @return string The first name
     */
    private function getFirstName(string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    /**
     * Get couple member names from the wedding.
     *
     * @param Wedding $wedding The wedding
     * @return Collection<int, string> Collection of couple member names
     */
    private function getCoupleNames(Wedding $wedding): Collection
    {
        return $wedding->couple()->get()->pluck('name');
    }

    /**
     * Get the first couple member name (noivo).
     * If only one person, returns that name.
     *
     * @param Collection<int, string> $names Couple names
     * @return string The noivo name
     */
    private function getNoivo(Collection $names): string
    {
        return $names->first() ?? '';
    }

    /**
     * Get the second couple member name (noiva).
     * If only one person, returns the same name as noivo.
     * If more than two, returns the second name.
     *
     * @param Collection<int, string> $names Couple names
     * @return string The noiva name
     */
    private function getNoiva(Collection $names): string
    {
        if ($names->count() <= 1) {
            return $names->first() ?? '';
        }

        return $names->get(1) ?? '';
    }

    /**
     * Get all couple names formatted.
     * - 1 person: "Name"
     * - 2 people: "Name1 e Name2"
     * - 3+ people: "Name1, Name2 e Name3"
     *
     * @param Collection<int, string> $names Couple names
     * @return string Formatted names
     */
    private function getNoivos(Collection $names): string
    {
        $count = $names->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $names->first();
        }

        if ($count === 2) {
            return $names->implode(' e ');
        }

        // 3+ people: "Name1, Name2 e Name3"
        $lastIndex = $count - 1;
        $allButLast = $names->slice(0, $lastIndex)->implode(', ');
        $last = $names->get($lastIndex);

        return $allButLast . ' e ' . $last;
    }

    /**
     * Format date in long Portuguese format.
     * Example: "15 de Março de 2025"
     *
     * @param Carbon|null $date The date to format
     * @return string Formatted date
     */
    private function formatDateLong(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        $day = $date->day;
        $month = self::MONTH_NAMES[$date->month];
        $year = $date->year;

        return "{$day} de {$month} de {$year}";
    }

    /**
     * Format date in short format.
     * Example: "15/03/2025"
     *
     * @param Carbon|null $date The date to format
     * @return string Formatted date
     */
    private function formatDateShort(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->format('d/m/Y');
    }

    /**
     * Format city and state.
     * Example: "São Paulo - SP"
     *
     * @param string|null $city The city
     * @param string|null $state The state
     * @return string Formatted location
     */
    private function formatCidadeEstado(?string $city, ?string $state): string
    {
        $city = $city ?? '';
        $state = $state ?? '';

        if (empty($city) && empty($state)) {
            return '';
        }

        if (empty($state)) {
            return $city;
        }

        if (empty($city)) {
            return $state;
        }

        return "{$city} - {$state}";
    }
}
