<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class AuthorizedCommandBus extends CommandBusDecoratorBase implements AuthorizedCommandBusInterface, LoggerAwareInterface, ContextAwareInterface
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var SecurityInterface
     */
    private $security;

    public function __construct(
        CommandBus $decoratee,
        UserIdentificationInterface $userIdentification,
        SecurityInterface $security
    ) {
        parent::__construct($decoratee);

        $this->userIdentification = $userIdentification;
        $this->security = $security;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($command)
    {
        if ($command instanceof AuthorizableCommandInterface) {
            $authorized = $this->isAuthorized($command);
        } else {
            $authorized = true;
        }

        if ($authorized) {
            parent::dispatch($command);
        } else {
            throw new CommandAuthorizationException(
                $this->userIdentification->getId(),
                $command
            );
        }
    }

    public function isAuthorized(AuthorizableCommandInterface $command): bool
    {
        return $this->security->isAuthorized($command);
    }

    public function getUserIdentification(): UserIdentificationInterface
    {
        return $this->userIdentification;
    }

    public function getUserId(): string
    {
        return $this->userIdentification->getId()->toNative();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->decoratee instanceof LoggerAwareInterface) {
            $this->decoratee->setLogger($logger);
        }
    }


    public function setContext(Metadata $context = null)
    {
        $this->metadata = $context;

        if ($this->decoratee instanceof ContextAwareInterface) {
            $this->decoratee->setContext($context);
        }
    }
}
