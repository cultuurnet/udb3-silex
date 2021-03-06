<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\AggregateRoot;

interface LabelAwareAggregateRoot extends AggregateRoot
{
    public function addLabel(Label $label);


    public function removeLabel(Label $label);
}
