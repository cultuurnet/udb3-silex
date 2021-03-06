<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;

abstract class AbstractPermissionCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $rolePermission;

    public function __construct(
        UUID $uuid,
        Permission $rolePermission
    ) {
        parent::__construct($uuid);

        // The built-in serialize call does not work on Enum.
        // Just store them internally as string but expose as Enum.
        $this->rolePermission = $rolePermission->toNative();
    }

    public function getRolePermission(): Permission
    {
        return Permission::fromNative($this->rolePermission);
    }
}
