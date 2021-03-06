<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsNot;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ReplayFilteringEventListener extends FilteringEventListener implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(EventListener $eventListener)
    {
        parent::__construct(
            $eventListener,
            new DomainMessageIsNot(
                new DomainMessageIsReplayed()
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        if ($this->eventListener instanceof LoggerAwareInterface) {
            $this->eventListener->setLogger($logger);
        }
    }
}
