<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class JwtV2Validator implements JwtValidator
{
    /**
     * @var JwtValidator
     */
    private $baseValidator;

    /**
     * @var string
     */
    private $v2JwtProviderAuth0ClientId;

    public function __construct(JwtValidator $baseValidator, string $v2JwtProviderAuth0ClientId)
    {
        $this->baseValidator = $baseValidator;
        $this->v2JwtProviderAuth0ClientId = $v2JwtProviderAuth0ClientId;
    }

    public function verifySignature(Token $token): void
    {
        $this->baseValidator->verifySignature($token);
    }

    public function validateClaims(Token $token): void
    {
        $this->baseValidator->validateClaims($token);

        $udb3Token = new Udb3Token($token);
        if ($this->isAccessToken($udb3Token)) {
            $this->validateAccessToken($udb3Token);
        } else {
            $this->validateIdToken($udb3Token);
        }
    }

    private function isAccessToken(Udb3Token $jwt): bool
    {
        // This does not 100% guarantee that the token is an access token, because an access token does not have an azp
        // if it has no specific aud. However we require our integrators to always include the "https://api.publiq.be"
        // aud, so access tokens should always have an azp in our case.
        return !is_null($jwt->getClientId());
    }

    private function validateAccessToken(Udb3Token $jwt): void
    {
        if (!$jwt->canUseEntryAPI()) {
            throw new AuthenticationException(
                'The given token and its related client are not allowed to access EntryAPI.',
                403
            );
        }
    }

    private function validateIdToken(Udb3Token $jwt): void
    {
        if (!$jwt->audienceContains($this->v2JwtProviderAuth0ClientId)) {
            throw new AuthenticationException(
                'Only legacy id tokens are supported. Please use an access token instead.'
            );
        }
    }
}
