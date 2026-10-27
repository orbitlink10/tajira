<?php

namespace App\Services;

class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp ??= time();
        $timeStep = 30;

        for ($offset = -$window; $offset <= $window; $offset++) {
            $counter = (int) floor($timestamp / $timeStep) + $offset;

            if (hash_equals($this->code($secret, $counter), $code)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(string $secret, string $account, string $issuer = 'Tajira'): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($account);

        return 'otpauth://totp/'.$label.'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public function code(string $secret, ?int $counter = null): string
    {
        $counter ??= (int) floor(time() / 30);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $key = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $bits = '';

        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';

        foreach (str_split($bits, 5) as $chunk) {
            $result .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $result;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
        $bits = '';

        foreach (str_split($secret) as $char) {
            $position = strpos(self::ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $result = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }

        return $result;
    }
}
