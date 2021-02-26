<?php

namespace CultuurNet\UDB3\Address;

interface CultureFeedAddressFactoryInterface
{
    /**
     * @return Address
     */
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress);
}
