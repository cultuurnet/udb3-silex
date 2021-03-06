<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

interface DomainMessageSpecificationInterface
{
    /**
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage);
}
