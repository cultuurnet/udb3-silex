<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Actor;

final class MockActorEvent extends ActorEvent
{
    public static function deserialize(array $data): MockActorEvent
    {
        return new static($data['actor_id']);
    }
}
