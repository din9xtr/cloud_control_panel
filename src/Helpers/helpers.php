<?php

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
