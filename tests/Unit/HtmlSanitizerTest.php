<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_editor_formatting_and_trusted_video_embeds_survive_saving(): void
    {
        $html = (new HtmlSanitizer)->clean('<p class="content-align-center" style="margin-left: 40px; color: red" onclick="alert(1)"><strong>Details</strong></p><table><tr><td>Specification</td></tr></table><iframe src="https://www.youtube.com/embed/abc123" allowfullscreen></iframe>');

        $this->assertStringContainsString('content-align-center', $html);
        $this->assertStringContainsString('margin-left: 40px', $html);
        $this->assertStringContainsString('<strong>Details</strong>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/abc123', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('color:', $html);
    }

    public function test_untrusted_embeds_and_executable_media_urls_are_removed(): void
    {
        $html = (new HtmlSanitizer)->clean('<iframe src="https://evil.example/embed/test"></iframe><iframe src="https://www.youtube.com.evil.example/embed/test"></iframe><video controls src="javascript:alert(1)" poster="javascript:alert(1)"></video><script>alert(1)</script>');

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}
