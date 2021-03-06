<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\PriceInfo\PriceInfo;

abstract class AbstractUpdatePriceInfo extends AbstractCommand
{
    /**
     * @var PriceInfo
     */
    protected $priceInfo;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, PriceInfo $priceInfo)
    {
        parent::__construct($itemId);
        $this->priceInfo = $priceInfo;
    }

    /**
     * @return PriceInfo
     */
    public function getPriceInfo()
    {
        return $this->priceInfo;
    }
}
