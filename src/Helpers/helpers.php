<?php

use Random\RandomException;

if (!function_exists('formatBytes')) {

    function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }
}
if (!function_exists('getStoragePercent')) {
    function getStoragePercent(int $categoryBytes, int $totalBytes): float
    {
        return $totalBytes > 0 ? ($categoryBytes / $totalBytes * 100) : 0;
    }
}
if (!function_exists('getClientIp')) {
    function getClientIp(): string
    {
        $server = $_SERVER;

        $candidates = [
            $server['HTTP_CF_CONNECTING_IP'] ?? null,

            extractForwardedIp($server['HTTP_FORWARDED'] ?? null),
            extractForwardedIp($server['HTTP_X_FORWARDED_FOR'] ?? null),
            extractForwardedIp($server['HTTP_FORWARDED_FOR'] ?? null),

            $server['HTTP_X_REAL_IP'] ?? null,
            $server['HTTP_CLIENT_IP'] ?? null,
            $server['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $ip) {
            if ($ip === null) {
                continue;
            }

            if ($valid = validateIp($ip)) {
                return $valid;
            }
        }

        return 'unknown';
    }
}

if (!function_exists('extractForwardedIp')) {
    function extractForwardedIp(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_contains($value, 'for=')) {
            if (preg_match('/for="?([^";, ]+)/i', $value, $m)) {
                return $m[1];
            }
        }

        $parts = explode(',', $value);

        return trim($parts[0]) ?: null;
    }
}

if (!function_exists('validateIp')) {
    function validateIp(string $ip): ?string
    {
        $ip = trim($ip);

        if ($ip === '') {
            return null;
        }

        if (
            str_contains($ip, ':') &&
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
        ) {
            $ip = explode(':', $ip, 2)[0];
        }

        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;

        return filter_var($ip, FILTER_VALIDATE_IP, $flags)
            ? $ip
            : null;
    }
}

if (!function_exists('encryptString')) {
    /**
     * XSalsa20 + Poly1305
     * @throws Throwable
     */
    function encryptString(string $plaintext): string
    {
        $key = hex2bin($_ENV['APP_KEY']); // 32 байта
        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY must be 32 bytes');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return base64_encode($nonce . $ciphertext);
    }
}

if (!function_exists('decryptString')) {
    /**
     * @throws Throwable
     */
    function decryptString(string $encrypted): string
    {
        $key = hex2bin($_ENV['APP_KEY']);
        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY must be 32 bytes');
        }

        $data = base64_decode($encrypted, true);
        $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt data');
        }

        return $plaintext;
    }
}
