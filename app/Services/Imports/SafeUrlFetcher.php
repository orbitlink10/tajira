<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class SafeUrlFetcher
{
    private const MAX_REDIRECTS = 3;

    /**
     * @return array{url:string,status:int,headers:array,body:string}
     */
    public function fetch(string $url, bool $allowImage = false, bool $enforceAllowlist = true, bool $throttle = true): array
    {
        if ($throttle) {
            $this->throttle();
        }

        $current = $url;

        for ($attempt = 0; $attempt <= self::MAX_REDIRECTS; $attempt++) {
            $this->validateUrl($current, $enforceAllowlist);
            $response = $this->request($current, $allowImage);

            if (in_array($response['status'], [301, 302, 303, 307, 308], true)) {
                $location = $response['headers']['location'] ?? null;

                if (! $location) {
                    throw new RuntimeException('The import source returned a redirect without a location.');
                }

                $current = $this->resolveUrl($current, $location);

                continue;
            }

            if ($response['status'] < 200 || $response['status'] >= 300) {
                throw new RuntimeException('The source returned HTTP '.$response['status'].'.');
            }

            return ['url' => $current] + $response;
        }

        throw new RuntimeException('Too many redirects while fetching the source URL.');
    }

    public function validateUrl(string $url, bool $enforceAllowlist = true): void
    {
        $parts = parse_url($url);

        if (! $parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new RuntimeException('Only HTTPS source URLs are allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('URLs with embedded credentials are not allowed.');
        }

        $allowedHosts = array_filter((array) config('tajira.imports.allowed_hosts', []));
        $host = strtolower($parts['host']);

        if ($enforceAllowlist && $allowedHosts !== [] && ! in_array($host, array_map('strtolower', $allowedHosts), true)) {
            throw new RuntimeException('The source host is not in the approved import allowlist.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        $ips = gethostbynamel($host);

        if (! $ips) {
            throw new RuntimeException('The source host could not be resolved.');
        }

        foreach ($ips as $ip) {
            $this->assertPublicIp($ip);
        }
    }

    private function assertPublicIp(string $ip): void
    {
        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        if (! $isPublic) {
            throw new RuntimeException('The source resolves to a private, reserved or loopback address.');
        }
    }

    private function resolveUrl(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$location;
        }

        $path = $parts['path'] ?? '/';
        $directory = substr($path, 0, strrpos($path, '/') + 1);

        return $scheme.'://'.$host.$directory.$location;
    }

    private function throttle(): void
    {
        $rate = max(1, (int) config('tajira.imports.rate_limit_per_minute', 20));
        $interval = 60 / $rate;
        $last = Cache::get('import.last_fetch_at');

        if ($last) {
            $wait = $interval - (microtime(true) - (float) $last);

            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
        }

        Cache::put('import.last_fetch_at', microtime(true), 300);
    }

    /**
     * @return array{status:int,headers:array,body:string}
     */
    private function request(string $url, bool $allowImage = false): array
    {
        $maxBytes = (int) config('tajira.imports.max_bytes', 5_000_000);
        $body = '';
        $headers = [];
        $tooLarge = false;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => (string) config('tajira.imports.user_agent'),
            CURLOPT_HTTPHEADER => [$allowImage ? 'Accept: image/jpeg,image/png,image/webp,image/gif' : 'Accept: text/html,application/xhtml+xml', 'Accept-Language: en'],
            CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$headers) {
                $length = strlen($line);
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return $length;
            },
            CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body, &$tooLarge, $maxBytes) {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $tooLarge = true;

                    return 0;
                }

                $body .= $chunk;

                return strlen($chunk);
            },
        ]);

        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = strtolower((string) ($headers['content-type'] ?? ''));
        $error = curl_error($handle);
        curl_close($handle);

        if ($tooLarge) {
            throw new RuntimeException('The source page exceeded the configured size limit.');
        }

        if ($result === false) {
            throw new RuntimeException('The source could not be fetched: '.$error);
        }

        $allowedTypes = ['text/html', 'application/xhtml', 'application/xml', 'text/xml', 'text/plain'];

        if ($allowImage) {
            $allowedTypes = array_merge($allowedTypes, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        }

        if ($contentType !== '' && ! collect($allowedTypes)->contains(fn ($type) => str_contains($contentType, $type))) {
            throw new RuntimeException('The source returned an unsupported content type.');
        }

        if (isset($headers['content-length']) && (int) $headers['content-length'] > $maxBytes) {
            throw new RuntimeException('The source page is too large.');
        }

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }
}
