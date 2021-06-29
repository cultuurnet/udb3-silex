<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Firewall;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtListenerTest extends TestCase
{
    /**
     * @var TokenStorageInterface|MockObject
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface|MockObject
     */
    private $authenticationManager;

    /**
     * @var Parser|MockObject
     */
    private $parser;

    /**
     * @var JwtListener
     */
    private $listener;

    /**
     * @var GetResponseEvent|MockObject
     */
    private $getResponseEvent;

    public function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $this->parser = $this->createMock(Parser::class);

        $this->listener = new JwtListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->parser
        );

        $this->getResponseEvent = $this->createMock(GetResponseEvent::class);
    }

    /**
     * @test
     * @dataProvider irrelevantRequestProvider
     *
     */
    public function it_ignores_irrelevant_requests(Request $request)
    {
        $this->getResponseEvent->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->parser->expects($this->never())
            ->method('parse');

        $this->authenticationManager->expects($this->never())
            ->method('authenticate');

        $this->tokenStorage->expects($this->never())
            ->method('setToken');

        $this->listener->handle($this->getResponseEvent);
    }

    /**
     * @return array
     */
    public function irrelevantRequestProvider()
    {
        return [
            [
                new Request([], [], [], [], [], [], ''),
            ],
            [
                new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'foo'], ''),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_authenticates_and_stores_valid_tokens()
    {
        $tokenString = 'headers.payload.signature';

        $jwt = new Jwt(
            ['alg' => 'none'],
            [],
            null,
            ['headers', 'payload']
        );

        $token = new JsonWebToken(new Udb3Token($jwt));
        $authenticatedToken = new JsonWebToken(new Udb3Token($jwt), true);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $tokenString],
            ''
        );

        $this->getResponseEvent->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($tokenString)
            ->willReturn($jwt);

        $this->authenticationManager->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($authenticatedToken);

        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with($authenticatedToken);

        $this->listener->handle($this->getResponseEvent);
    }

    /**
     * @test
     */
    public function it_returns_an_unauthorized_response_if_jwt_authentication_fails()
    {
        $tokenString = 'headers.payload.signature';

        $jwt = new Jwt(
            ['alg' => 'none'],
            [],
            null,
            ['headers', 'payload']
        );

        $token = new JsonWebToken(new Udb3Token($jwt));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $tokenString],
            ''
        );

        $this->getResponseEvent->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($tokenString)
            ->willReturn($jwt);

        $authenticationException = new AuthenticationException(
            'Authentication failed',
            666
        );

        $this->authenticationManager->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willThrowException($authenticationException);

        $this->getResponseEvent->expects($this->once())
            ->method('setResponse')
            ->willReturnCallback(
                function (Response $response) {
                    $this->assertEquals(401, $response->getStatusCode());
                }
            );

        $this->listener->handle($this->getResponseEvent);
    }
}
