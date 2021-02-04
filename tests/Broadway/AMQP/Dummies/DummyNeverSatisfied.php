<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Dummies;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationInterface;

class DummyNeverSatisfied implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return false;
    }
}
