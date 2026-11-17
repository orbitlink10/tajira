<?php

namespace Tests\Unit;

use App\Support\YoutubeVideo;
use PHPUnit\Framework\TestCase;

class YoutubeVideoTest extends TestCase
{
    public function test_supported_youtube_urls_resolve_to_the_same_id(): void
    {
        foreach (['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30', 'https://youtu.be/dQw4w9WgXcQ?si=test', 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'] as $url) {
            $this->assertSame('dQw4w9WgXcQ', YoutubeVideo::id($url));
        }
    }

    public function test_invalid_urls_cannot_become_embeds(): void
    {
        foreach ([null, '', 'javascript:alert(1)', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', 'https://youtube.com/watch?v[]=bad', 'https://youtube.com/watch?v=bad', 'https://evil.test/dQw4w9WgXcQ'] as $url) {
            $this->assertNull(YoutubeVideo::id($url));
        }
    }
}
