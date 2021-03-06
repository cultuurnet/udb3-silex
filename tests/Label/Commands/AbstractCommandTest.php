<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractCommandTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var AbstractCommand|MockObject
     */
    private $abstractCommand;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->abstractCommand = $this->getMockForAbstractClass(
            AbstractCommand::class,
            [$this->uuid]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->abstractCommand->getUuid());
    }

    /**
     * @test
     */
    public function it_has_an_item_id()
    {
        $this->assertEquals(
            $this->uuid->toNative(),
            $this->abstractCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_has_permission_aanbod_labelen()
    {
        $this->assertEquals(
            Permission::LABELS_BEHEREN(),
            $this->abstractCommand->getPermission()
        );
    }
}
