<?php

declare(strict_types=1);

namespace App\Services\Site;

/**
 * Represents the result of a validation operation.
 * 
 * Contains validation status, errors, and warnings.
 */
class ValidationResult
{
    /**
     * Whether the validation passed.
     */
    private bool $valid;

    /**
     * Array of error messages.
     */
    private array $errors;

    /**
     * Array of warning messages.
     */
    private array $warnings;

    /**
     * Create a new ValidationResult instance.
     *
     * @param bool $valid Whether validation passed
     * @param array $errors Array of error messages
     * @param array $warnings Array of warning messages
     */
    public function __construct(bool $valid = true, array $errors = [], array $warnings = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    /**
     * Check if the validation passed.
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get all error messages.
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warning messages.
     *
     * @return array Array of warning messages
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Add an error message and mark as invalid.
     *
     * @param string $error The error message to add
     * @return self
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->valid = false;
        return $this;
    }

    /**
     * Add a warning message.
     *
     * @param string $warning The warning message to add
     * @return self
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * Check if there are any errors.
     *
     * @return bool True if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings.
     *
     * @return bool True if there are warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Create a successful validation result.
     *
     * @return self
     */
    public static function success(): self
    {
        return new self(true, [], []);
    }

    /**
     * Create a failed validation result with an error.
     *
     * @param string $error The error message
     * @return self
     */
    public static function failure(string $error): self
    {
        return new self(false, [$error], []);
    }

    /**
     * Merge another ValidationResult into this one.
     *
     * @param ValidationResult $other The other result to merge
     * @return self
     */
    public function merge(ValidationResult $other): self
    {
        $this->errors = array_merge($this->errors, $other->getErrors());
        $this->warnings = array_merge($this->warnings, $other->getWarnings());
        
        if (!$other->isValid()) {
            $this->valid = false;
        }
        
        return $this;
    }
}
