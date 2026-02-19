<?php

declare(strict_types=1);

namespace App\Contracts\Site;

use App\Models\Wedding;

/**
 * Interface for placeholder replacement service.
 * 
 * Provides methods to replace placeholders in site content with actual
 * wedding data (couple names, date, venue, etc.).
 * 
 * Supported placeholders:
 * - {nome_1} / {nome_2} → couple member names
 * - {noivo} / {noiva} → legacy aliases for couple member names
 * - {primeiro_nome_1} / {primeiro_nome_2} → first names of couple members
 * - {primeiro_nome_noivo} / {primeiro_nome_noiva} → legacy first-name aliases
 * - {noivos} → all couple names separated by " e "
 * - {data_extenso} → formatted wedding date (e.g., "15 de Março de 2025")
 * - {data_simples} → short date format (e.g., "15/03/2025")
 * - {data} → formatted wedding date (e.g., "15 de Março de 2025")
 * - {data_curta} → short date format (e.g., "15/03/2025")
 * - {local} → venue name
 * - {cidade} → city
 * - {estado} → state
 * - {cidade_estado} → "city - state"
 */
interface PlaceholderServiceInterface
{
    /**
     * Replace all placeholders in a string with wedding data.
     * 
     * @param string $content The content containing placeholders
     * @param Wedding $wedding The wedding to get data from
     * @return string The content with placeholders replaced
     */
    public function replacePlaceholders(string $content, Wedding $wedding): string;

    /**
     * Recursively replace placeholders in all string values of an array.
     * 
     * @param array $content The array containing strings with placeholders
     * @param Wedding $wedding The wedding to get data from
     * @return array The array with all placeholders replaced
     */
    public function replaceInArray(array $content, Wedding $wedding): array;

    /**
     * Get list of all available placeholders with descriptions.
     * 
     * @return array<string, string> Associative array of placeholder => description
     */
    public function getAvailablePlaceholders(): array;
}
