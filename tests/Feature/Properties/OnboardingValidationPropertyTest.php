<?php

namespace Tests\Feature\Properties;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Validation Properties
 *
 * Property 2: creator_name is required
 * Property 3: partner_name is optional and bounded
 */
class OnboardingValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: creator_name must always be present.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function creator_name_is_required(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $validator = Validator::make(
                [
                    'creator_name' => '',
                ],
                [
                    'creator_name' => ['required', 'string', 'max:255'],
                ]
            );

            $this->assertTrue(
                $validator->fails(),
                "creator_name should be required (iteration {$i})"
            );
        }
    }

    /**
     * Property: partner_name can be empty.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_name_is_optional(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $validator = Validator::make(
                [
                    'partner_name' => '',
                ],
                [
                    'partner_name' => ['nullable', 'string', 'max:255'],
                ]
            );

            $this->assertFalse(
                $validator->fails(),
                "partner_name should be optional (iteration {$i})"
            );
        }
    }

    /**
     * Property: partner_name accepts bounded valid values.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_name_accepts_valid_values_within_limit(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $name = fake()->name();

            $validator = Validator::make(
                [
                    'partner_name' => $name,
                ],
                [
                    'partner_name' => ['nullable', 'string', 'max:255'],
                ]
            );

            $this->assertFalse(
                $validator->fails(),
                "partner_name '{$name}' should pass validation (iteration {$i})"
            );
        }
    }
}
