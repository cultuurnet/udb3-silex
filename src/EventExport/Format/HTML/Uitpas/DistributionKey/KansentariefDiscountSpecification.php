<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

class KansentariefDiscountSpecification implements DistributionKeySpecification
{
    /**
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_DistributionKey $distributionKey)
    {
        $satisfied = false;
        foreach ($distributionKey->conditions as $condition) {
            if ($condition->definition == $condition::DEFINITION_KANSARM) {
                $satisfied = true;
                break;
            }
        }
        return $satisfied;
    }
}
