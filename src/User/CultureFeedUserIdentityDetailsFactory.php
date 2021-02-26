<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class CultureFeedUserIdentityDetailsFactory implements CultureFeedUserIdentityDetailsFactoryInterface
{
    /**
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUser(\CultureFeed_User $cfUser)
    {
        return $this->createFromCultureFeedUserObject($cfUser);
    }

    /**
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUserSearchResult(\CultureFeed_SearchUser $cfSearchUser)
    {
        return $this->createFromCultureFeedUserObject($cfSearchUser);
    }

    /**
     * @param \CultureFeed_User|\CultureFeed_SearchUser $cfUserObject
     * @return UserIdentityDetails
     */
    private function createFromCultureFeedUserObject($cfUserObject)
    {
        return new UserIdentityDetails(
            new StringLiteral($cfUserObject->id),
            new StringLiteral($cfUserObject->nick),
            new EmailAddress($cfUserObject->mbox)
        );
    }
}
