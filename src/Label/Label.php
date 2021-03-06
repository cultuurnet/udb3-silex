<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Label\Events\CopyCreated;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Label extends EventSourcedAggregateRoot
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Privacy
     */
    private $privacy;

    /**
     * @var UUID
     */
    private $parentUuid;


    public function getAggregateRootId(): string
    {
        return $this->uuid->toNative();
    }

    /**
     * @return Label
     */
    public static function create(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy
    ) {
        $label = new Label();

        $label->apply(new Created(
            $uuid,
            $name,
            $visibility,
            $privacy
        ));

        return $label;
    }

    /**
     * @return Label
     */
    public static function createCopy(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid
    ) {
        $label = new Label();

        $label->apply(new CopyCreated(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $parentUuid
        ));

        return $label;
    }

    public function makeVisible()
    {
        if ($this->visibility !== Visibility::VISIBLE()) {
            $this->apply(new MadeVisible($this->uuid, $this->name));
        }
    }

    public function makeInvisible()
    {
        if ($this->visibility !== Visibility::INVISIBLE()) {
            $this->apply(new MadeInvisible($this->uuid, $this->name));
        }
    }

    public function makePublic()
    {
        if ($this->privacy !== Privacy::PRIVACY_PUBLIC()) {
            $this->apply(new MadePublic($this->uuid, $this->name));
        }
    }

    public function makePrivate()
    {
        if ($this->privacy !== Privacy::PRIVACY_PRIVATE()) {
            $this->apply(new MadePrivate($this->uuid, $this->name));
        }
    }


    public function applyCreated(Created $created)
    {
        $this->uuid = $created->getUuid();
        $this->name = $created->getName();
        $this->visibility = $created->getVisibility();
        $this->privacy = $created->getPrivacy();
    }


    public function applyCopyCreated(CopyCreated $copyCreated)
    {
        $this->applyCreated($copyCreated);

        $this->parentUuid = $copyCreated->getParentUuid();
    }


    public function applyMadeVisible(MadeVisible $madeVisible)
    {
        $this->visibility = Visibility::VISIBLE();
    }


    public function applyMadeInvisible(MadeInvisible $madeInvisible)
    {
        $this->visibility = Visibility::INVISIBLE();
    }


    public function applyMadePublic(MadePublic $madePublic)
    {
        $this->privacy = Privacy::PRIVACY_PUBLIC();
    }


    public function applyMadePrivate(MadePrivate $madePrivate)
    {
        $this->privacy = Privacy::PRIVACY_PRIVATE();
    }
}
