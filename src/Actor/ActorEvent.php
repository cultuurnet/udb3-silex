<?php

namespace CultuurNet\UDB3\Actor;

use Broadway\Serializer\Serializable;

abstract class ActorEvent implements Serializable
{
    /**
     * @var string
     */
    protected $actorId;

    public function __construct($actorId)
    {
        $this->actorId = $actorId;
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }

    public function serialize(): array
    {
        return [
            'actor_id' => $this->actorId,
        ];
    }
}
