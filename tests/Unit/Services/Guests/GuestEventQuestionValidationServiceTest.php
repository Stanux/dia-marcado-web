<?php

namespace Tests\Unit\Services\Guests;

use App\Models\GuestEvent;
use App\Services\Guests\GuestEventQuestionValidationService;
use App\Services\Guests\RsvpSubmissionException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestEventQuestionValidationServiceTest extends TestCase
{
    #[Test]
    public function it_accepts_response_using_backend_default_key_pattern(): void
    {
        $event = new GuestEvent();
        $event->questions = [[
            'label' => 'Quantas pessoas da sua família irão?',
            'type' => 'number',
            'required' => true,
        ]];

        $validated = app(GuestEventQuestionValidationService::class)
            ->validateForEvent($event, ['q_1' => 2]);

        $this->assertSame(2, $validated['q_1']);
        $this->assertSame(2, $validated['quantas_pessoas_da_sua_familia_irao']);
    }

    #[Test]
    public function it_accepts_legacy_zero_based_question_key_pattern(): void
    {
        $event = new GuestEvent();
        $event->questions = [[
            'label' => 'Quantas pessoas da sua família irão?',
            'type' => 'number',
            'required' => true,
        ]];

        $validated = app(GuestEventQuestionValidationService::class)
            ->validateForEvent($event, ['q_0' => 3]);

        $this->assertSame(3, $validated['q_0']);
    }

    #[Test]
    public function it_accepts_number_with_comma_separator(): void
    {
        $event = new GuestEvent();
        $event->questions = [[
            'label' => 'Quantas pessoas da sua família irão?',
            'type' => 'number',
            'required' => true,
        ]];

        $validated = app(GuestEventQuestionValidationService::class)
            ->validateForEvent($event, ['q_1' => '2,5']);

        $this->assertSame('2,5', $validated['q_1']);
    }

    #[Test]
    public function it_rejects_non_numeric_response_for_number_question(): void
    {
        $this->expectException(RsvpSubmissionException::class);
        $this->expectExceptionMessage('deve ser numerica');

        $event = new GuestEvent();
        $event->questions = [[
            'label' => 'Quantas pessoas da sua família irão?',
            'type' => 'number',
            'required' => true,
        ]];

        app(GuestEventQuestionValidationService::class)
            ->validateForEvent($event, ['q_1' => 'duas pessoas']);
    }
}

