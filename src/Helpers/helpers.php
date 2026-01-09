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
