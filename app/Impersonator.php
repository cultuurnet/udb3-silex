<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;

class Impersonator
{
    /**
     * @var string|null
     */
    private $userId;

    /**
     * @var JsonWebToken|null
     */
    private $jwt;

    /**
     * @var ApiKey|null
     */
    private $apiKey;

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getJwt(): ?JsonWebToken
    {
        return $this->jwt;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function impersonate(Metadata $metadata): void
    {
        $metadata = $metadata->serialize();

        $this->userId = $metadata['user_id'];
        $this->jwt = new JsonWebToken($metadata['auth_jwt'], true) ?? null;
        $this->apiKey = $metadata['auth_api_key'] ?? null;
    }
}
