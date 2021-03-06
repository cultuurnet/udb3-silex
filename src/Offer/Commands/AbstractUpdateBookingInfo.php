<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\BookingInfo;

abstract class AbstractUpdateBookingInfo extends AbstractCommand
{
    /**
     * The bookingInfo entry
     * @var BookingInfo
     */
    protected $bookingInfo;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, BookingInfo $bookingInfo)
    {
        parent::__construct($itemId);
        $this->bookingInfo = $bookingInfo;
    }

    /**
     * @return BookingInfo
     */
    public function getBookingInfo()
    {
        return $this->bookingInfo;
    }
}
