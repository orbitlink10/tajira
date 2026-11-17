<?php

namespace App\Support;

class YoutubeVideo
{
    public static function id(?string $url): ?string
    {
        $parts = parse_url(trim($url ?? ''));
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true) || isset($parts['user']) || isset($parts['port'])) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('#^/(?:shorts|embed)/([^/]+)/?$#', $path, $match)) {
                $id = $match[1];
            }
        }
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : null;
    }
}
