<?php
declare(strict_types=1);

namespace Din9xtrCloud\Services;

use Din9xtrCloud\Repositories\UserRepository;
use Din9xtrCloud\Repositories\SessionRepository;

final readonly class LoginService
{
    public function __construct(
        private UserRepository    $users,
        private SessionRepository $sessions
    )
    {
    }

    public function attemptLogin(
        string  $username,
        string  $password,
        ?string $ip = null,
        ?string $userAgent = null
    ): ?string
    {
        $user = $this->users->findByUsername($username);

        if (!$user) {
            return null;
        }

        if (!$user->verifyPassword($password)) {
            return null;
        }

        $session = $this->sessions->create(
            $user->id,
            $ip,
            $userAgent
        );

        return $session->authToken;
    }

    public function logout(string $token): void
    {
        $this->sessions->revokeByToken($token);
    }
}
