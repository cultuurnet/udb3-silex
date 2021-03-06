<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;
use Broadway\Domain\Metadata;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class SimpleContextAwareCommandBus implements CommandBus, ContextAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Metadata
     */
    protected $context;

    /**
     * @var CommandHandler[]
     */
    private $commandHandlers = [];

    public function setContext(Metadata $context = null)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(CommandHandler $handler)
    {
        $this->commandHandlers[] = $handler;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($command)
    {

        /** @var CommandHandler|ContextAwareInterface|LoggerAwareInterface $handler */
        foreach ($this->commandHandlers as $handler) {
            if ($this->logger && $handler instanceof LoggerAwareInterface) {
                $handler->setLogger($this->logger);
            }

            if ($handler instanceof ContextAwareInterface) {
                $handler->setContext($this->context);
            }
            $handler->handle($command);
        }
    }
}
