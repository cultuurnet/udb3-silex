<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use ValueObjects\Web\Url;

class UpdateWebsite extends AbstractUpdateOrganizerCommand
{
    /**
     * @var Url
     */
    private $website;

    /**
     * UpdateUrl constructor.
     * @param string $organizerId
     */
    public function __construct(
        $organizerId,
        Url $website
    ) {
        parent::__construct($organizerId);
        $this->website = $website;
    }

    /**
     * @return Url
     */
    public function getWebsite()
    {
        return $this->website;
    }
}
