<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\ContactPoint;

class UpdateContactPoint extends AbstractUpdateOrganizerCommand
{
    /**
     * @var ContactPoint
     */
    private $contactPoint;

    /**
     * UpdateContactPoint constructor.
     * @param string $organizerId
     */
    public function __construct(
        $organizerId,
        ContactPoint $contactPoint
    ) {
        parent::__construct($organizerId);
        $this->contactPoint = $contactPoint;
    }

    /**
     * @return ContactPoint
     */
    public function getContactPoint()
    {
        return $this->contactPoint;
    }
}
