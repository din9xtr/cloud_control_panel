<?php
declare(strict_types=1);

namespace Din9xtrCloud\Storage;

use RuntimeException;

final class UserStorageInitializer
{
    private const array CATEGORIES = [
        'documents',
        'media',
    ];

    public function __construct(
        private readonly string $basePath
    )
    {
    }

    /**
     * @throws RuntimeException
     */
    public function init(int|string $userId): void
    {
        $userPath = $this->basePath . '/users/' . $userId;

        foreach (self::CATEGORIES as $dir) {
            $path = $userPath . '/' . $dir;

            if (!is_dir($path)) {
                if (!mkdir($path, 0775, true)) {
                    throw new RuntimeException(
                        sprintf('can`t crate dir: %s', $path)
                    );
                }
            }
        }
    }
}
