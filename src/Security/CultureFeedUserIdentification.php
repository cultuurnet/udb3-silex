<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultureFeed_User;
use ValueObjects\StringLiteral\StringLiteral;

class CultureFeedUserIdentification implements UserIdentificationInterface
{
    /**
     * @var CultureFeed_User
     */
    private $cultureFeedUser;

    /**
     * @var string[][]
     */
    private $permissionList;

    /**
     * @param string[][] $permissionList
     */
    public function __construct(
        CultureFeed_User $cultureFeedUser,
        array $permissionList
    ) {
        $this->cultureFeedUser = $cultureFeedUser;
        $this->permissionList = $permissionList;
    }


    /**
     * @return bool
     */
    public function isGodUser()
    {
        return in_array(
            $this->cultureFeedUser->id,
            $this->permissionList['allow_all']
        );
    }

    /**
     * @return StringLiteral
     */
    public function getId()
    {
        return new StringLiteral($this->cultureFeedUser->id);
    }
}
