<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class JsonWebToken extends AbstractToken
{
    private const TIME_LEEWAY = 30;

    /**
     * @var Token
     */
    private $jwt;

    public function __construct(Token $jwt, bool $authenticated = false)
    {
        parent::__construct();
        $this->setAuthenticated($authenticated);
        $this->jwt = $jwt;
    }

    public function getUserId(): string
    {
        if ($this->jwt->hasClaim('uid')) {
            return $this->jwt->getClaim('uid');
        }

        if ($this->jwt->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->jwt->getClaim('https://publiq.be/uitidv1id');
        }

        return $this->jwt->getClaim('sub');
    }

    public function getClientId(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->jwt->hasClaim('azp')) {
            return (string) $this->jwt->getClaim('azp');
        }
        return null;
    }

    public function hasClaims(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->jwt->hasClaim($name)) {
                return false;
            }
        }
        return true;
    }

    public function isUsableAtCurrentTime(): bool
    {
        // Use the built-in validation provided by Lcobucci without any extra validation data.
        // This will automatically validate the time-sensitive claims.
        // Set the leeway to 30 seconds so we can compensate for slight clock skew between auth0 and our own servers.
        // @see https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
        return $this->jwt->validate(new ValidationData(null, self::TIME_LEEWAY));
    }

    public function hasValidIssuer(array $validIssuers): bool
    {
        return in_array($this->jwt->getClaim('iss', ''), $validIssuers, true);
    }

    public function hasAudience(string $audience): bool
    {
        if (!$this->jwt->hasClaim('aud')) {
            return false;
        }

        // The aud claim can be a string or an array. Convert string to array with one value for consistency.
        $aud = $this->jwt->getClaim('aud');
        if (is_string($aud)) {
            $aud = [$aud];
        }

        return in_array($audience, $aud, true);
    }

    public function hasEntryApiInPubliqApisClaim(): bool
    {
        $apis = $this->jwt->getClaim('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }

    public function getCredentials(): Token
    {
        return $this->jwt;
    }
}
