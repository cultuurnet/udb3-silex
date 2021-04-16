<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;

class RetryingCommandBus extends CommandBusDecoratorBase
{
    public const MAX_RETRIES = 3;

    public function dispatch($command)
    {
        return $this->decoratee->dispatch($command);
    }
}
