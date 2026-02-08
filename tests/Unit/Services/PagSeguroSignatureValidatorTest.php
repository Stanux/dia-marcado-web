<?php

namespace Tests\Unit\Services;

use App\Services\Payment\PagSeguroSignatureValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PagSeguroSignatureValidatorTest extends TestCase
{
    private PagSeguroSignatureValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set a test webhook secret
        Config::set('services.pagseguro.webhook_secret', 'test-secret-key');
        
        $this->validator = new PagSeguroSignatureValidator();
    }

    /** @test */
    public function it_validates_correct_signature()
    {
        $payload = '{"event_type":"CHARGE.PAID","data":{"id":"12345"}}';
        $expectedSignature = hash_hmac('sha256', $payload, 'test-secret-key');

        $result = $this->validator->validate($payload, $expectedSignature);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_incorrect_signature()
    {
        Log::shouldReceive('warning')->once();

        $payload = '{"event_type":"CHARGE.PAID","data":{"id":"12345"}}';
        $wrongSignature = 'wrong-signature';

        $result = $this->validator->validate($payload, $wrongSignature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_empty_signature()
    {
        Log::shouldReceive('warning')->once();

        $payload = '{"event_type":"CHARGE.PAID"}';
        $emptySignature = '';

        $result = $this->validator->validate($payload, $emptySignature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_when_webhook_secret_not_configured()
    {
        Log::shouldReceive('warning')->once();

        Config::set('services.pagseguro.webhook_secret', '');
        $validator = new PagSeguroSignatureValidator();

        $payload = '{"event_type":"CHARGE.PAID"}';
        $signature = 'any-signature';

        $result = $validator->validate($payload, $signature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_uses_timing_safe_comparison()
    {
        $payload = '{"event_type":"CHARGE.PAID"}';
        $correctSignature = hash_hmac('sha256', $payload, 'test-secret-key');
        
        // Create a signature that differs only in the last character
        $almostCorrectSignature = substr($correctSignature, 0, -1) . 'x';

        $result = $this->validator->validate($payload, $almostCorrectSignature);

        // Should still reject even though it's very similar
        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_different_payloads_correctly()
    {
        $payload1 = '{"event_type":"CHARGE.PAID","data":{"id":"12345"}}';
        $payload2 = '{"event_type":"CHARGE.DECLINED","data":{"id":"67890"}}';

        $signature1 = hash_hmac('sha256', $payload1, 'test-secret-key');
        $signature2 = hash_hmac('sha256', $payload2, 'test-secret-key');

        // Correct combinations
        $this->assertTrue($this->validator->validate($payload1, $signature1));
        $this->assertTrue($this->validator->validate($payload2, $signature2));

        // Incorrect combinations (mismatched payload and signature)
        Log::shouldReceive('warning')->twice();
        $this->assertFalse($this->validator->validate($payload1, $signature2));
        $this->assertFalse($this->validator->validate($payload2, $signature1));
    }
}
