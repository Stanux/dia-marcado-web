<?php

declare(strict_types=1);

namespace App\Services\Site;

/**
 * Represents the result of a QA checklist run.
 * 
 * Contains individual check results with status, messages, and section info.
 */
class QAResult
{
    /**
     * Check status constants.
     */
    public const STATUS_PASSED = 'pass';
    public const STATUS_FAILED = 'fail';
    public const STATUS_WARNING = 'warning';

    /**
     * Whether all required checks passed.
     */
    private bool $passed;

    /**
     * Array of check results.
     * Each check: ['name' => string, 'status' => string, 'message' => string, 'section' => string|null]
     */
    private array $checks;

    /**
     * Create a new QAResult instance.
     *
     * @param bool $passed Whether all required checks passed
     * @param array $checks Array of check results
     */
    public function __construct(bool $passed = true, array $checks = [])
    {
        $this->passed = $passed;
        $this->checks = $checks;
    }

    /**
     * Check if all required checks passed.
     *
     * @return bool True if all required checks passed
     */
    public function isPassed(): bool
    {
        return $this->passed;
    }

    /**
     * Get all check results.
     *
     * @return array Array of all check results
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    /**
     * Get only failed checks.
     *
     * @return array Array of failed check results
     */
    public function getFailedChecks(): array
    {
        return array_filter($this->checks, function (array $check) {
            return $check['status'] === self::STATUS_FAILED;
        });
    }

    /**
     * Get only warning checks.
     *
     * @return array Array of warning check results
     */
    public function getWarnings(): array
    {
        return array_filter($this->checks, function (array $check) {
            return $check['status'] === self::STATUS_WARNING;
        });
    }

    /**
     * Get only passed checks.
     *
     * @return array Array of passed check results
     */
    public function getPassedChecks(): array
    {
        return array_filter($this->checks, function (array $check) {
            return $check['status'] === self::STATUS_PASSED;
        });
    }

    /**
     * Determine if the site can be published based on QA results.
     * 
     * A site can be published if there are no failed checks.
     * Warnings do not block publication.
     *
     * @return bool True if site can be published
     */
    public function canPublish(): bool
    {
        return empty($this->getFailedChecks());
    }

    /**
     * Add a check result.
     *
     * @param string $name The check name
     * @param string $status The check status (passed, failed, warning)
     * @param string $message The check message
     * @param string|null $section The section this check applies to
     * @return self
     */
    public function addCheck(string $name, string $status, string $message, ?string $section = null): self
    {
        $this->checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'section' => $section,
        ];

        if ($status === self::STATUS_FAILED) {
            $this->passed = false;
        }

        return $this;
    }

    /**
     * Add a passed check.
     *
     * @param string $name The check name
     * @param string $message The check message
     * @param string|null $section The section this check applies to
     * @return self
     */
    public function addPassedCheck(string $name, string $message, ?string $section = null): self
    {
        return $this->addCheck($name, self::STATUS_PASSED, $message, $section);
    }

    /**
     * Add a failed check.
     *
     * @param string $name The check name
     * @param string $message The check message
     * @param string|null $section The section this check applies to
     * @return self
     */
    public function addFailedCheck(string $name, string $message, ?string $section = null): self
    {
        return $this->addCheck($name, self::STATUS_FAILED, $message, $section);
    }

    /**
     * Add a warning check.
     *
     * @param string $name The check name
     * @param string $message The check message
     * @param string|null $section The section this check applies to
     * @return self
     */
    public function addWarningCheck(string $name, string $message, ?string $section = null): self
    {
        return $this->addCheck($name, self::STATUS_WARNING, $message, $section);
    }

    /**
     * Get the count of checks by status.
     *
     * @return array ['passed' => int, 'failed' => int, 'warning' => int]
     */
    public function getCounts(): array
    {
        return [
            'passed' => count($this->getPassedChecks()),
            'failed' => count($this->getFailedChecks()),
            'warning' => count($this->getWarnings()),
        ];
    }
}
