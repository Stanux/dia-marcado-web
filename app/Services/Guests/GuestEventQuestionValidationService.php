<?php

namespace App\Services\Guests;

use App\Models\GuestEvent;
use Illuminate\Support\Str;

class GuestEventQuestionValidationService
{
    /**
     * @param  array<string, mixed>|null  $responses
     * @return array<string, mixed>
     *
     * @throws RsvpSubmissionException
     */
    public function validateForEvent(GuestEvent $event, ?array $responses): array
    {
        $responses = is_array($responses) ? $responses : [];
        $questions = is_array($event->questions) ? $event->questions : [];

        if ($questions === []) {
            return $responses;
        }

        $normalized = $responses;

        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }

            $label = trim((string) ($question['label'] ?? 'Pergunta ' . ((int) $index + 1)));
            $type = $this->normalizeType($question['type'] ?? null);
            $key = $this->resolveKey($question, (int) $index);
            $candidateKeys = $this->candidateKeys($question, (int) $index, $key);

            [$hasValue, $value] = $this->extractResponse($responses, $candidateKeys);
            $required = $this->toBool($question['required'] ?? false);

            if ($required && (!$hasValue || $this->isEmpty($value))) {
                throw new RsvpSubmissionException("A pergunta '{$label}' e obrigatoria.", 422);
            }

            if (!$hasValue || $this->isEmpty($value)) {
                continue;
            }

            $this->validateValueType(
                label: $label,
                type: $type,
                value: $value,
                options: is_array($question['options'] ?? null) ? $question['options'] : [],
            );

            if (!array_key_exists($key, $normalized)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  mixed  $rawType
     */
    private function normalizeType(mixed $rawType): string
    {
        $type = strtolower(trim((string) $rawType));

        return in_array($type, ['text', 'textarea', 'select', 'number'], true)
            ? $type
            : 'text';
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function resolveKey(array $question, int $index): string
    {
        $key = trim((string) ($question['key'] ?? ''));

        if ($key !== '') {
            return $key;
        }

        $label = trim((string) ($question['label'] ?? ''));
        $slug = Str::slug($label, '_');

        if ($slug !== '') {
            return $slug;
        }

        return 'q_' . ($index + 1);
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<int, string>
     */
    private function candidateKeys(array $question, int $index, string $resolvedKey): array
    {
        $label = trim((string) ($question['label'] ?? ''));

        return collect([
            $resolvedKey,
            trim((string) ($question['key'] ?? '')),
            $label,
            // Backward compatibility with legacy frontend fallback (0-based).
            'q_' . $index,
            'q_' . ($index + 1),
        ])
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $responses
     * @param  array<int, string>  $candidateKeys
     * @return array{0:bool,1:mixed}
     */
    private function extractResponse(array $responses, array $candidateKeys): array
    {
        foreach ($candidateKeys as $key) {
            if (array_key_exists($key, $responses)) {
                return [true, $responses[$key]];
            }
        }

        return [false, null];
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return false;
    }

    /**
     * @param  mixed  $value
     * @param  array<int, mixed>  $options
     *
     * @throws RsvpSubmissionException
     */
    private function validateValueType(string $label, string $type, mixed $value, array $options): void
    {
        if ($type === 'number') {
            $normalizedValue = is_string($value)
                ? str_replace(',', '.', trim($value))
                : $value;

            if (is_numeric($normalizedValue)) {
                return;
            }

            throw new RsvpSubmissionException("A resposta da pergunta '{$label}' deve ser numerica.", 422);
        }

        if (in_array($type, ['text', 'textarea'], true) && !is_scalar($value)) {
            throw new RsvpSubmissionException("A resposta da pergunta '{$label}' deve ser texto.", 422);
        }

        if ($type !== 'select') {
            return;
        }

        if (!is_scalar($value)) {
            throw new RsvpSubmissionException("A resposta da pergunta '{$label}' e invalida.", 422);
        }

        $valueString = trim((string) $value);
        $normalizedOptions = collect($options)
            ->filter(fn ($option): bool => is_scalar($option) && trim((string) $option) !== '')
            ->map(fn ($option): string => trim((string) $option))
            ->values();

        if ($normalizedOptions->isEmpty()) {
            return;
        }

        $valid = $normalizedOptions
            ->contains(fn (string $option): bool => mb_strtolower($option) === mb_strtolower($valueString));

        if (!$valid) {
            throw new RsvpSubmissionException("Resposta invalida para a pergunta '{$label}'.", 422);
        }
    }
}
