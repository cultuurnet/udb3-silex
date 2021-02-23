<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\EventHandling\EventBus;

interface ReplayModeEventBusInterface extends EventBus
{
    public function startReplayMode();

    public function stopReplayMode();
}
