<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RoleLabelsProjectorTest extends TestCase
{
    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelJsonRepository;

    /**
     * @var DocumentRepository|MockObject
     */
    private $labelRolesRepository;

    /**
     * @var RoleLabelsProjector
     */
    private $roleLabelsProjector;

    /**
     * @var DocumentRepository|MockObject
     */
    private $roleLabelsRepository;

    public function setUp()
    {
        $this->roleLabelsRepository = $this->createMock(DocumentRepository::class);
        $this->labelJsonRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRolesRepository = $this->createMock(DocumentRepository::class);

        $this->roleLabelsProjector = new RoleLabelsProjector(
            $this->roleLabelsRepository,
            $this->labelJsonRepository,
            $this->labelRolesRepository
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_labels_on_role_created_event()
    {
        $roleCreated = new RoleCreated(
            new UUID(),
            new StringLiteral('roleName')
        );

        $domainMessage = $this->createDomainMessage(
            $roleCreated->getUuid(),
            $roleCreated
        );

        $jsonDocument = $this->createEmptyJsonDocument($roleCreated->getUuid());
        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_projection_on_role_deleted_event()
    {
        $roleDeleted = new RoleDeleted(
            new UUID()
        );

        $domainMessage = $this->createDomainMessage(
            $roleDeleted->getUuid(),
            $roleDeleted
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('remove')
            ->with($roleDeleted->getUuid());

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_label_details_on_label_added_event()
    {
        $labelAdded = new LabelAdded(
            new UUID(),
            new UUID()
        );

        $domainMessage = $this->createDomainMessage(
            $labelAdded->getUuid(),
            $labelAdded
        );

        $labelEntity = $this->createLabelEntity($labelAdded->getLabelId());

        $this->mockLabelJsonGet(
            $labelAdded->getLabelId(),
            $labelEntity
        );

        $jsonDocument = $this->createEmptyJsonDocument($labelAdded->getUuid());

        $this->mockRoleLabelsFetch(
            $labelAdded->getUuid(),
            $jsonDocument
        );

        $expectedJsonDocument = $this->createJsonDocument(
            $labelAdded->getUuid(),
            $labelAdded->getLabelId()
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($expectedJsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_label_details_from_projection_on_label_removed_event()
    {
        $labelRemoved = new LabelRemoved(
            new UUID(),
            new UUID()
        );

        $domainMessage = $this->createDomainMessage(
            $labelRemoved->getUuid(),
            $labelRemoved
        );

        $labelEntity = $this->createLabelEntity($labelRemoved->getLabelId());

        $this->mockLabelJsonGet(
            $labelRemoved->getLabelId(),
            $labelEntity
        );

        $jsonDocument = $this->createJsonDocument(
            $labelRemoved->getUuid(),
            $labelRemoved->getLabelId()
        );

        $this->mockRoleLabelsFetch(
            $labelRemoved->getUuid(),
            $jsonDocument
        );

        $expectedJsonDocument = $this->createEmptyJsonDocument(
            $labelRemoved->getUuid()
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($expectedJsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projections_with_label_details_on_label_details_projected_to_json_ld()
    {
        $labelProjected = new LabelDetailsProjectedToJSONLD(
            new UUID()
        );

        $roleId = new UUID();

        $domainMessage = $this->createDomainMessage(
            $labelProjected->getUuid(),
            $labelProjected
        );

        $jsonDocument = new JsonDocument(
            $labelProjected->getUuid(),
            json_encode([$roleId->toNative() => $roleId->toNative()])
        );

        $this->labelRolesRepository
            ->method('fetch')
            ->with($labelProjected->getUuid()->toNative())
            ->willReturn($jsonDocument);

        $jsonDocument = $this->createJsonDocument($roleId, $labelProjected->getUuid());

        $this->mockRoleLabelsFetch($roleId, $jsonDocument);

        $labelEntity = $this->createLabelEntity($labelProjected->getUuid());

        $this->mockLabelJsonGet($labelProjected->getUuid(), $labelEntity);


        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    private function createDomainMessage(
        UUID $uuid,
        Serializable $payload
    ): DomainMessage {
        return new DomainMessage(
            $uuid,
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    /**
     * @return JsonDocument
     */
    private function createEmptyJsonDocument(UUID $uuid)
    {
        return new JsonDocument(
            $uuid,
            json_encode([])
        );
    }

    /**
     * @return JsonDocument
     */
    public function createJsonDocument(UUID $uuid, UUID $labelId)
    {
        return new JsonDocument(
            $uuid,
            json_encode([$labelId->toNative() => $this->createLabelEntity($labelId)])
        );
    }

    /**
     * @return Entity
     */
    public function createLabelEntity(UUID $uuid)
    {
        return new Entity(
            $uuid,
            new StringLiteral('labelName'),
            Visibility::getByName('INVISIBLE'),
            Privacy::getByName('PRIVACY_PRIVATE')
        );
    }

    private function mockRoleLabelsFetch(UUID $uuid, JsonDocument $jsonDocument): void
    {
        $this->roleLabelsRepository
            ->method('fetch')
            ->with($uuid->toNative())
            ->willReturn($jsonDocument);
    }


    private function mockLabelJsonGet(UUID $uuid, Entity $entity)
    {
        $this->labelJsonRepository
            ->method('getByUuid')
            ->with($uuid)
            ->willReturn(
                $entity
            );
    }
}
