<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\TestCase;

final class Udb3TokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_uid_claim_as_id_if_present(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'uid' => new Basic('uid', '6e3ef9b3-e37b-428e-af30-05f3a96dbbe4'),
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('6e3ef9b3-e37b-428e-af30-05f3a96dbbe4', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_uitid_v1_claim_as_id_if_present(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('b55f041e-5c5e-4850-9fb8-8cf73d538c56', $token->id());
    }

    /**
     * @test
     */
    public function it_returns_sub_claim_as_id(): void
    {
        $token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $this->assertEquals('auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87', $token->id());
    }
}
