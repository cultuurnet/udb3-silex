<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class GodUserVoter implements PermissionVoterInterface
{
    /**
     * @var array
     */
    private $godUserIds;

    /**
     * @param string[] $godUserIds
     */
    public function __construct(array $godUserIds)
    {
        $this->godUserIds = $godUserIds;
    }

    /**
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $offerId,
        StringLiteral $userId
    ) {
        return in_array($userId->toNative(), $this->godUserIds);
    }
}
