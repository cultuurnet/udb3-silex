<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class OrganizerCreationPayload
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var ContactPoint
     */
    private $contactPoint;


    public function __construct(
        Language $mainLanguage,
        Url $website,
        Title $title,
        Address $address = null,
        ContactPoint $contactPoint = null
    ) {
        $this->mainLanguage = $mainLanguage;
        $this->website = $website;
        $this->title = $title;
        $this->address = $address;
        $this->contactPoint = $contactPoint;
    }

    /**
     * @return Language
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    /**
     * @return Url
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return Address|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return ContactPoint|null
     */
    public function getContactPoint()
    {
        return $this->contactPoint;
    }
}
