<?php

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use CultuurNet\UDB3\Collection\AbstractCollection;

class SpecificationCollection extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return SpecificationInterface::class;
    }
}
