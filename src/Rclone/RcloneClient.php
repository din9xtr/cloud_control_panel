<?php
declare(strict_types=1);

namespace Din9xtrCloud\Rclone;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

final readonly class RcloneClient
{
    public function __construct(
        private ClientInterface         $http,
        private RequestFactoryInterface $requests,
        private StreamFactoryInterface  $streams,
        private string                  $baseUrl,
        private string                  $user,
        private string                  $pass,
    )
    {
    }

    /**
     * @param string $method rc endpoint without
     * @param array $params JSON body
     * @return array
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function call(string $method, array $params = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($method, '/');

        $body = $this->streams->createStream(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $request = $this->requests
            ->createRequest('POST', $url)
            ->withHeader(
                'Authorization',
                'Basic ' . base64_encode($this->user . ':' . $this->pass)
            )
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $response = $this->http->sendRequest($request);

        $status = $response->getStatusCode();
        $content = (string)$response->getBody();

        if ($status >= 400) {
            throw new RuntimeException(
                sprintf('Rclone API error %d: %s', $status, $content)
            );
        }

        if ($content === '') {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
