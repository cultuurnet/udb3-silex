<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class DeleteOrganizer extends AbstractOrganizerCommand implements AuthorizableCommandInterface
{
    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEHEREN();
    }
}
