<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use ValueObjects\StringLiteral\StringLiteral;

interface LabelSecurityInterface
{
    /**
     * @return StringLiteral[]
     */
    public function getNames(): array;
}
