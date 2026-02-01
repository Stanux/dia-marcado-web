<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when media upload fails.
 */
class MediaUploadException extends Exception
{
    /**
     * The validation errors.
     */
    private array $errors;

    /**
     * Create a new MediaUploadException.
     *
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = 'Media upload failed',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception for validation failure.
     *
     * @param array $errors The validation errors
     * @return self
     */
    public static function validationFailed(array $errors): self
    {
        return new self('File validation failed', $errors);
    }

    /**
     * Create exception for malware detection.
     *
     * @return self
     */
    public static function malwareDetected(): self
    {
        return new self('File rejected for security reasons', ['File appears to be malicious']);
    }

    /**
     * Create exception for storage quota exceeded.
     *
     * @param int $limit The storage limit in bytes
     * @return self
     */
    public static function storageQuotaExceeded(int $limit): self
    {
        $limitMb = round($limit / 1024 / 1024);
        return new self(
            'Storage quota exceeded',
            ["Storage limit of {$limitMb}MB has been reached for this wedding"]
        );
    }
}
