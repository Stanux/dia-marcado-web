<?php

namespace Tests\Feature\Properties;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 3: Validação de Data Futura
 * 
 * For any date provided as the wedding date:
 * - If the date is in the past, validation should fail
 * - If the date is in the future or today, validation should pass
 * 
 * Validates: Requirements 4.2
 */
class WeddingDateValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validate if a date is valid for wedding (today or future).
     */
    private function isValidWeddingDate(string $date): bool
    {
        $weddingDate = Carbon::parse($date)->startOfDay();
        $today = Carbon::now()->startOfDay();
        
        return $weddingDate->greaterThanOrEqualTo($today);
    }

    /**
     * Property test: Past dates are invalid
     * 
     * For any date in the past, validation should fail.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function past_dates_are_invalid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random past date (1 to 365 days ago)
            $daysAgo = rand(1, 365);
            $pastDate = Carbon::now()->subDays($daysAgo)->format('Y-m-d');
            
            $isValid = $this->isValidWeddingDate($pastDate);
            
            $this->assertFalse(
                $isValid,
                "Past date ($pastDate) should be invalid (iteration $i)"
            );
        }
    }

    /**
     * Property test: Future dates are valid
     * 
     * For any date in the future, validation should pass.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function future_dates_are_valid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random future date (1 to 730 days from now)
            $daysAhead = rand(1, 730);
            $futureDate = Carbon::now()->addDays($daysAhead)->format('Y-m-d');
            
            $isValid = $this->isValidWeddingDate($futureDate);
            
            $this->assertTrue(
                $isValid,
                "Future date ($futureDate) should be valid (iteration $i)"
            );
        }
    }

    /**
     * Property test: Today is valid
     * 
     * Today's date should be valid for a wedding.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function today_is_valid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $today = Carbon::now()->format('Y-m-d');
            
            $isValid = $this->isValidWeddingDate($today);
            
            $this->assertTrue(
                $isValid,
                "Today's date ($today) should be valid (iteration $i)"
            );
        }
    }

    /**
     * Property test: Date validation is consistent
     * 
     * For any date, the validation result should be consistent
     * with whether the date is >= today.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function date_validation_is_consistent(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random date (past or future)
            $daysOffset = rand(-365, 365);
            $randomDate = Carbon::now()->addDays($daysOffset)->format('Y-m-d');
            
            $isValid = $this->isValidWeddingDate($randomDate);
            $expectedValid = Carbon::parse($randomDate)->startOfDay()
                ->greaterThanOrEqualTo(Carbon::now()->startOfDay());
            
            $this->assertEquals(
                $expectedValid,
                $isValid,
                "Validation for date ($randomDate) should be consistent (iteration $i)"
            );
        }
    }

    /**
     * Property test: Yesterday is always invalid
     * 
     * Yesterday's date should always be invalid.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function yesterday_is_always_invalid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $yesterday = Carbon::now()->subDay()->format('Y-m-d');
            
            $isValid = $this->isValidWeddingDate($yesterday);
            
            $this->assertFalse(
                $isValid,
                "Yesterday's date ($yesterday) should be invalid (iteration $i)"
            );
        }
    }
}
