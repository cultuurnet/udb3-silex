<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class RoleUsersProjectorTest extends TestCase
{
    /**
     * @var DocumentRepository|MockObject
     */
    private $repository;

    /**
     * @var UserIdentityResolver|MockObject
     */
    private $userIdentityResolver;

    /**
     * @var RoleUsersProjector
     */
    private $roleUsersProjector;

    /**
     * @var UserIdentityDetails
     */
    private $userIdentityDetail;

    protected function setUp()
    {
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->userIdentityResolver = $this->createMock(
            UserIdentityResolver::class
        );

        $this->roleUsersProjector = new RoleUsersProjector(
            $this->repository,
            $this->userIdentityResolver
        );

        $this->userIdentityDetail = new UserIdentityDetails(
            new StringLiteral('userId'),
            new StringLiteral('username'),
            new EmailAddress('username@company.be')
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_users_on_role_created_event()
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
        $this->repository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleUsersProjector->handle($domainMessage);
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

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($roleDeleted->getUuid());

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_user_identity_on_user_added_event()
    {
        $userAdded = new UserAdded(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->mockFetch(
            $userAdded->getUuid(),
            $this->createEmptyJsonDocument($userAdded->getUuid())
        );

        $this->mockGetUserById(
            $this->userIdentityDetail->getUserId(),
            $this->userIdentityDetail
        );

        $jsonDocument = $this->createJsonDocumentWithUserIdentityDetail(
            $userAdded->getUuid(),
            $this->userIdentityDetail
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_user_identity_from_projection_on_user_removed_event()
    {
        $userRemoved = new UserRemoved(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $this->mockFetch(
            $userRemoved->getUuid(),
            $this->createJsonDocumentWithUserIdentityDetail(
                $userRemoved->getUuid(),
                $this->userIdentityDetail
            )
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->createEmptyJsonDocument($userRemoved->getUuid()));

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_document_not_found_on_user_added_event()
    {
        $userAdded = new UserAdded(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->repository
            ->method('fetch')
            ->with($userAdded->getUuid()->toNative())
            ->willThrowException(DocumentDoesNotExist::withId($userAdded->getUuid()->toNative()));

        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_user_details_not_found_on_user_added_event()
    {
        $userAdded = new UserAdded(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->mockFetch(
            $userAdded->getUuid(),
            $this->createEmptyJsonDocument($userAdded->getUuid())
        );

        $this->mockGetUserById(new StringLiteral('userId'));

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('userId'));

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_document_not_found_on_user_removed_event()
    {
        $userRemoved = new UserRemoved(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $this->repository
            ->method('fetch')
            ->with($userRemoved->getUuid()->toNative())
            ->willThrowException(DocumentDoesNotExist::withId($userRemoved->getUuid()->toNative()));

        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @param JsonDocument $jsonDocument
     */
    private function mockFetch(UUID $uuid, JsonDocument $jsonDocument = null)
    {
        $this->repository
            ->method('fetch')
            ->with($uuid)
            ->willReturn($jsonDocument);
    }

    /**
     * @param UserIdentityDetails $userIdentityDetails
     */
    private function mockGetUserById(
        StringLiteral $userId,
        UserIdentityDetails $userIdentityDetails = null
    ) {
        $this->userIdentityResolver
            ->method('getUserById')
            ->with($userId)
            ->willReturn($userIdentityDetails);
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
    private function createJsonDocumentWithUserIdentityDetail(
        UUID $uuid,
        UserIdentityDetails $userIdentityDetail
    ) {
        $userIdentityDetails = [];

        $key = $userIdentityDetail->getUserId()->toNative();
        $userIdentityDetails[$key] = $userIdentityDetail;

        return new JsonDocument($uuid, json_encode($userIdentityDetails));
    }
}
