<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Properties 2, 3: Validation Properties
 * 
 * Property 2: Partner email validation
 * Property 3: Partner field dependency
 * 
 * Validates: Requirements 3.5, 3.6, 7.2
 */
class OnboardingValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2: Partner email validation
     * 
     * For any string provided as partner email:
     * - Invalid email format should fail validation
     * - Email same as creator should fail validation
     * - Valid email different from creator should pass
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_email_validation_works_correctly(): void
    {
        $creatorEmail = 'creator@example.com';

        // Test invalid email formats
        $invalidEmails = [
            'notanemail',
            '@nodomain.com',
            'spaces in@email.com',
            'double@@at.com',
            '.startswithdot@email.com',
            'email@',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $validator = Validator::make(
                ['partner_email' => $invalidEmail],
                ['partner_email' => 'email']
            );
            $this->assertTrue(
                $validator->fails(),
                "Invalid email '{$invalidEmail}' should fail validation"
            );
        }

        // Test email same as creator
        $validator = Validator::make(
            ['partner_email' => $creatorEmail, 'creator_email' => $creatorEmail],
            ['partner_email' => 'different:creator_email']
        );
        $this->assertTrue(
            $validator->fails(),
            "Partner email same as creator should fail validation"
        );

        // Test valid emails different from creator
        for ($i = 0; $i < 100; $i++) {
            $validEmail = "valid-partner-{$i}@example.com";
            $validator = Validator::make(
                ['partner_email' => $validEmail, 'creator_email' => $creatorEmail],
                ['partner_email' => 'email|different:creator_email']
            );
            $this->assertFalse(
                $validator->fails(),
                "Valid email '{$validEmail}' different from creator should pass validation (iteration $i)"
            );
        }
    }

    /**
     * Property 3: Partner field dependency
     * 
     * For any form state:
     * - If partner email is filled, partner name should be required
     * - If partner email is empty, partner name can be empty
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_name_is_required_when_email_is_provided(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Test: email filled, name empty - should fail
            $validator = Validator::make(
                [
                    'partner_email' => "partner-{$i}@example.com",
                    'partner_name' => '',
                ],
                [
                    'partner_name' => 'required_with:partner_email',
                ]
            );
            $this->assertTrue(
                $validator->fails(),
                "Partner name should be required when email is provided (iteration $i)"
            );

            // Test: email filled, name filled - should pass
            $validator = Validator::make(
                [
                    'partner_email' => "partner-{$i}@example.com",
                    'partner_name' => "Partner Name {$i}",
                ],
                [
                    'partner_name' => 'required_with:partner_email',
                ]
            );
            $this->assertFalse(
                $validator->fails(),
                "Validation should pass when both email and name are provided (iteration $i)"
            );

            // Test: email empty, name empty - should pass
            $validator = Validator::make(
                [
                    'partner_email' => '',
                    'partner_name' => '',
                ],
                [
                    'partner_name' => 'required_with:partner_email',
                ]
            );
            $this->assertFalse(
                $validator->fails(),
                "Validation should pass when both email and name are empty (iteration $i)"
            );

            // Test: email empty, name filled - should pass
            $validator = Validator::make(
                [
                    'partner_email' => '',
                    'partner_name' => "Partner Name {$i}",
                ],
                [
                    'partner_name' => 'required_with:partner_email',
                ]
            );
            $this->assertFalse(
                $validator->fails(),
                "Validation should pass when email is empty but name is filled (iteration $i)"
            );
        }
    }

    /**
     * Property 2 (continued): Email format validation with random strings
     * 
     * Generate random strings and verify email validation works.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function random_strings_are_validated_correctly(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random string (likely not a valid email)
            $randomString = bin2hex(random_bytes(rand(5, 20)));
            
            $validator = Validator::make(
                ['email' => $randomString],
                ['email' => 'email']
            );
            
            // Random strings should almost always fail email validation
            // (unless by extreme chance they form a valid email)
            $isValidEmail = filter_var($randomString, FILTER_VALIDATE_EMAIL) !== false;
            
            $this->assertEquals(
                !$isValidEmail,
                $validator->fails(),
                "Random string '{$randomString}' validation should match filter_var result (iteration $i)"
            );
        }
    }
}
