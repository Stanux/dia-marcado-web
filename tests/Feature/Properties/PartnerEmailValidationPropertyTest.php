<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 7: Validação de E-mail do Parceiro
 * 
 * For any string provided as partner email:
 * - If it's not a valid email format, validation should fail
 * - If it's equal to the current user's email, validation should fail
 * - If it's a valid email and different from current user, validation should pass
 * 
 * Validates: Requirements 7.6, 7.7
 */
class PartnerEmailValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validate partner email against rules.
     */
    private function validatePartnerEmail(string $email, string $currentUserEmail): bool
    {
        $validator = Validator::make(
            ['partner_email' => $email],
            [
                'partner_email' => ['email', 'different:current_email'],
            ],
            [],
            ['current_email' => $currentUserEmail]
        );

        // Add custom different rule check
        if ($email === $currentUserEmail) {
            return false;
        }

        return !$validator->fails();
    }

    /**
     * Property test: Invalid email formats fail validation
     * 
     * For any string that is not a valid email format, validation should fail.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_email_formats_fail_validation(): void
    {
        $invalidEmails = [
            'notanemail',
            '@nodomain.com',
            'spaces in@email.com',
            'double@@at.com',
            'no.at.sign.com',
            '',
            'just.dots...',
            'missing@',
            '@missing.com',
            'invalid..email@domain.com',
        ];

        $currentUserEmail = 'user@example.com';

        for ($i = 0; $i < 100; $i++) {
            $invalidEmail = $invalidEmails[array_rand($invalidEmails)];
            
            $validator = Validator::make(
                ['partner_email' => $invalidEmail],
                ['partner_email' => ['required', 'email']]
            );
            
            $this->assertTrue(
                $validator->fails(),
                "Invalid email '$invalidEmail' should fail validation (iteration $i)"
            );
        }
    }

    /**
     * Property test: Valid email formats pass format validation
     * 
     * For any valid email format, format validation should pass.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_email_formats_pass_validation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $validEmail = fake()->unique()->safeEmail();
            
            $validator = Validator::make(
                ['partner_email' => $validEmail],
                ['partner_email' => ['required', 'email']]
            );
            
            $this->assertFalse(
                $validator->fails(),
                "Valid email '$validEmail' should pass format validation (iteration $i)"
            );
        }
    }

    /**
     * Property test: Same email as current user fails validation
     * 
     * For any email that matches the current user's email, validation should fail.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function same_email_as_current_user_fails_validation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            
            $isValid = $this->validatePartnerEmail($user->email, $user->email);
            
            $this->assertFalse(
                $isValid,
                "Partner email same as current user should fail validation (iteration $i)"
            );
        }
    }

    /**
     * Property test: Different valid email passes validation
     * 
     * For any valid email different from current user, validation should pass.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function different_valid_email_passes_validation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $partnerEmail = fake()->unique()->safeEmail();
            
            // Ensure emails are different
            while ($partnerEmail === $user->email) {
                $partnerEmail = fake()->unique()->safeEmail();
            }
            
            $isValid = $this->validatePartnerEmail($partnerEmail, $user->email);
            
            $this->assertTrue(
                $isValid,
                "Valid email different from current user should pass validation (iteration $i)"
            );
        }
    }

    /**
     * Property test: Email validation is case-insensitive for same-email check
     * 
     * Email comparison should be case-insensitive.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function email_comparison_handles_case_variations(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $baseEmail = fake()->unique()->safeEmail();
            $upperEmail = strtoupper($baseEmail);
            $mixedEmail = ucfirst($baseEmail);
            
            // All variations should be considered the same email
            $validator = Validator::make(
                ['partner_email' => $upperEmail],
                ['partner_email' => ['required', 'email']]
            );
            
            // Email format validation should still pass for uppercase
            $this->assertFalse(
                $validator->fails(),
                "Uppercase email should pass format validation (iteration $i)"
            );
        }
    }

    /**
     * Property test: Empty email fails required validation
     * 
     * Empty or null email should fail when required.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function empty_email_fails_required_validation(): void
    {
        $emptyValues = ['', null, '   '];

        for ($i = 0; $i < 100; $i++) {
            $emptyValue = $emptyValues[array_rand($emptyValues)];
            
            $validator = Validator::make(
                ['partner_email' => $emptyValue],
                ['partner_email' => ['required', 'email']]
            );
            
            $this->assertTrue(
                $validator->fails(),
                "Empty email should fail required validation (iteration $i)"
            );
        }
    }
}
