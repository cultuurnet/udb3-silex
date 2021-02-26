<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

class Sapi3SearchQueryFactoryTest extends SearchQueryFactoryTestBase
{
    protected function setUp()
    {
        $this->searchQueryFactory = new Sapi3SearchQueryFactory();
    }

    /**
     * @return string
     */
    public function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ) {
        $constraintStr = $constraint->toNative();
        $offerIdStr = $offerId->toNative();

        return '((' . $constraintStr . ') AND id:' . $offerIdStr . ')';
    }
}
