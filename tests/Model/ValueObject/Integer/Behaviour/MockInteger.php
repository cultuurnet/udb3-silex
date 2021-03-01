<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

class MockInteger
{
    use IsInteger;

    public function __construct($value)
    {
        $this->setValue($value);
    }
}
