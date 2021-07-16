<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class JsonWebToken extends AbstractToken
{
    public const V1_JWT_PROVIDER_TOKEN = 'v1_jwt_provider_token';
    public const V2_JWT_PROVIDER_TOKEN = 'v2_jwt_provider_token';
    public const V2_USER_ACCESS_TOKEN = 'v2_user_access_token';
    public const V2_CLIENT_ACCESS_TOKEN = 'v2_client_access_token';

    private const TIME_LEEWAY = 30;

    /**
     * @var string
     */
    private $jwt;

    /**
     * @var Token
     */
    private $token;

    /**
     * @throws InvalidArgumentException
     *   If the provided JWT string cannot be parsed
     */
    public function __construct(string $jwt, bool $authenticated = false)
    {
        parent::__construct();
        $this->setAuthenticated($authenticated);
        $this->jwt = $jwt;
        $this->token = (new Parser())->parse($jwt);
    }

    public function authenticate(): JsonWebToken
    {
        return new self($this->getCredentials(), true);
    }

    /**
     * @return string
     *   One of the V1/V2_..._TOKEN constants.
     */
    public function getType(): string
    {
        // V1 tokens had a non-standardized "uid" claim
        if ($this->token->hasClaim('uid')) {
            return self::V1_JWT_PROVIDER_TOKEN;
        }

        // V2 tokens from the JWT provider are Auth0 ID tokens and do not have an azp claim
        if (!$this->token->hasClaim('azp')) {
            return self::V2_JWT_PROVIDER_TOKEN;
        }

        // V2 client access tokens are always requested using the client-credentials grant type (gty)
        // @see https://stackoverflow.com/questions/49492471/whats-the-meaning-of-the-gty-claim-in-a-jwt-token/49492971
        if ($this->token->getClaim('gty', '') === 'client-credentials') {
            return self::V2_CLIENT_ACCESS_TOKEN;
        }

        // If all other checks fail it's a V2 user access token.
        return self::V2_USER_ACCESS_TOKEN;
    }

    public function getUserId(): string
    {
        if ($this->token->hasClaim('uid')) {
            return $this->token->getClaim('uid');
        }

        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->token->getClaim('https://publiq.be/uitidv1id');
        }

        return $this->token->getClaim('sub');
    }

    public function getUserIdentityDetails(UserIdentityResolver $userIdentityResolver): ?UserIdentityDetails
    {
        if ($this->getType() === self::V2_CLIENT_ACCESS_TOKEN) {
            return null;
        }

        // Tokens from V1 JWT provider (= custom)
        if ($this->hasClaims(['nick', 'email'])) {
            return new UserIdentityDetails(
                new StringLiteral($this->getUserId()),
                new StringLiteral($this->token->getClaim('nick')),
                new EmailAddress($this->token->getClaim('email'))
            );
        }

        // Tokens from V2 JWT provider (= Auth0 ID tokens)
        if ($this->hasClaims(['nickname', 'email'])) {
            return new UserIdentityDetails(
                new StringLiteral($this->getUserId()),
                new StringLiteral($this->token->getClaim('nickname')),
                new EmailAddress($this->token->getClaim('email'))
            );
        }

        try {
            return $userIdentityResolver->getUserById(new StringLiteral($this->getUserId()));
        } catch (Exception $e) {
            return null;
        }
    }

    public function getClientId(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->token->hasClaim('azp')) {
            return (string) $this->token->getClaim('azp');
        }
        return null;
    }

    public function hasClaims(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->token->hasClaim($name)) {
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
        return $this->token->validate(new ValidationData(null, self::TIME_LEEWAY));
    }

    public function hasValidIssuer(array $validIssuers): bool
    {
        return in_array($this->token->getClaim('iss', ''), $validIssuers, true);
    }

    public function hasAudience(string $audience): bool
    {
        if (!$this->token->hasClaim('aud')) {
            return false;
        }

        // The aud claim can be a string or an array. Convert string to array with one value for consistency.
        $aud = $this->token->getClaim('aud');
        if (is_string($aud)) {
            $aud = [$aud];
        }

        return in_array($audience, $aud, true);
    }

    public function hasEntryApiInPubliqApisClaim(): bool
    {
        $apis = $this->token->getClaim('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }

    public function verifyRsaSha256Signature(string $publicKey, ?string $keyPassphrase = null): bool
    {
        $signer = new Sha256();
        $key = new Key($publicKey, $keyPassphrase);
        return $this->token->verify($signer, $key);
    }

    public function getCredentials(): string
    {
        return $this->jwt;
    }
}
