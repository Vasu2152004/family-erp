<?php

declare(strict_types=1);

namespace App\Support;

final class BlobPathNormalizer
{
    private const PREFIXES = ['storage/', 'public/', 'private/'];

    /**
     * Normalize a blob path for Vercel Blob (no leading slash, no filesystem prefixes).
     */
    public static function normalize(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = trim($path);
        $path = ltrim($path, '/');

        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        $path = trim($path);

        return $path === '' ? null : $path;
    }
}
