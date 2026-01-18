<?php
declare(strict_types=1);

namespace Din9xtrCloud\Rclone;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class RcloneICloudConfigurator
{
    public function __construct(
        private RcloneClient $rclone,
    )
    {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function createRemote(
        string $remote,
        string $appleId
    ): void
    {
        $this->rclone->call('config/create', [
            'name' => $remote,
            'type' => 'iclouddrive',
            'parameters' => [
                'apple_id' => $appleId,
            ],
            'opt' => [
                'nonInteractive' => true,
            ],
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function setPassword(
        string $remote,
        string $password
    ): void
    {
        $this->rclone->call('config/password', [
            'name' => $remote,
            'parameters' => [
                'password' => $password,
            ],
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function submit2fa(
        string $remote,
        string $code
    ): void
    {
        $this->rclone->call('config/update', [
            'name' => $remote,
            'parameters' => [
                'config_2fa' => $code,
            ],
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getConfig(string $remote): array
    {
        return $this->rclone->call('config/show', [
            'name' => $remote
        ]);
    }

}
