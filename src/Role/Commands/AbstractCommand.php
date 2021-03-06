<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;

abstract class AbstractCommand implements AuthorizableCommandInterface
{
    /**
     * @var UUID
     */
    private $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getItemId(): string
    {
        return (string) $this->getUuid();
    }

    public function getPermission(): Permission
    {
        return Permission::GEBRUIKERS_BEHEREN();
    }
}
