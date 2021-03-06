<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\Events\CopyCreated;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\AbstractProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use ValueObjects\Identity\UUID;

class Projector extends AbstractProjector
{
    /**
     * @var WriteRepositoryInterface
     */
    private $writeRepository;

    /**
     * @var ReadRepositoryInterface
     */
    private $readRepository;

    /**
     * Projector constructor.
     */
    public function __construct(
        WriteRepositoryInterface $writeRepository,
        ReadRepositoryInterface $readRepository
    ) {
        $this->writeRepository = $writeRepository;
        $this->readRepository = $readRepository;
    }


    public function applyCreated(Created $created)
    {
        $labelWithSameUuid = $this->readRepository->getByUuid($created->getUuid());
        $labelWithSameName = $this->readRepository->getByName($created->getName());

        if ($labelWithSameUuid ||  $labelWithSameName) {
            return;
        }
        $this->writeRepository->save(
            $created->getUuid(),
            $created->getName(),
            $created->getVisibility(),
            $created->getPrivacy()
        );
    }


    public function applyCopyCreated(CopyCreated $copyCreated)
    {
        $labelWithSameUuid = $this->readRepository->getByUuid($copyCreated->getUuid());
        $labelWithSameName = $this->readRepository->getByName($copyCreated->getName());

        if ($labelWithSameUuid ||  $labelWithSameName) {
            return;
        }

        $this->writeRepository->save(
            $copyCreated->getUuid(),
            $copyCreated->getName(),
            $copyCreated->getVisibility(),
            $copyCreated->getPrivacy(),
            $copyCreated->getParentUuid()
        );
    }


    public function applyMadeVisible(MadeVisible $madeVisible)
    {
        $this->writeRepository->updateVisible($madeVisible->getUuid());
    }


    public function applyMadeInvisible(MadeInvisible $madeInvisible)
    {
        $this->writeRepository->updateInvisible($madeInvisible->getUuid());
    }


    public function applyMadePublic(MadePublic $madePublic)
    {
        $this->writeRepository->updatePublic($madePublic->getUuid());
    }


    public function applyMadePrivate(MadePrivate $madePrivate)
    {
        $this->writeRepository->updatePrivate($madePrivate->getUuid());
    }

    /**
     * @inheritdoc
     */
    public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata)
    {
        $uuid = $this->getUuid($labelAdded);

        if ($uuid) {
            $this->writeRepository->updateCountIncrement($uuid);
        }
    }

    /**
     * @inheritdoc
     */
    public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata)
    {
        $uuid = $this->getUuid($labelRemoved);

        if ($uuid) {
            $this->writeRepository->updateCountDecrement($uuid);
        }
    }

    /**
     * @inheritdoc
     */
    public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata)
    {
        // This projector does not handle this event, but it is part of abstract projector.
    }

    /**
     * @return UUID|null
     */
    private function getUuid(LabelEventInterface $labelEvent)
    {
        $uuid = null;

        $name = $labelEvent->getLabel()->getName();
        $entity = $this->readRepository->getByName($name);

        if ($entity !== null) {
            $uuid = $entity->getUuid();
        }

        return $uuid;
    }
}
