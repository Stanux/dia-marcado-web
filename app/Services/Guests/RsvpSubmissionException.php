<?php

namespace App\Services\Guests;

class RsvpSubmissionException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
