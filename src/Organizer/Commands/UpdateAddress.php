<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;

class UpdateAddress extends AbstractUpdateOrganizerCommand
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @var Language
     */
    private $language;

    /**
     * UpdateAddress constructor.
     * @param string $organizerId
     */
    public function __construct(
        $organizerId,
        Address $address,
        Language $language
    ) {
        parent::__construct($organizerId);
        $this->address = $address;
        $this->language = $language;
    }


    public function getAddress(): Address
    {
        return $this->address;
    }


    public function getLanguage(): Language
    {
        return $this->language;
    }
}
