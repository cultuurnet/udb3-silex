<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Timestamp;

interface CalendarJSONParserInterface
{
    public function getStartDate(array $data): ?\DateTimeInterface;

    public function getEndDate(array $data): ?\DateTimeInterface;

    /**
     * @return Timestamp[]
     */
    public function getTimestamps(array $data): array;

    /**
     * @return OpeningHour[]
     */
    public function getOpeningHours(array $data): array;
}
