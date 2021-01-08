<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Timestamp;

class CalendarJSONParser implements CalendarJSONParserInterface
{
    /**
     * @param array $data
     *
     * @return \DateTimeInterface|null
     */
    public function getStartDate($data)
    {
        if (!isset($data['startDate'])) {
            return null;
        }

        return new \DateTime($data['startDate']);
    }

    /**
     * @param array $data
     *
     * @return \DateTimeInterface|null
     */
    public function getEndDate($data)
    {
        if (!isset($data['endDate'])) {
            return null;
        }

        return new \DateTime($data['endDate']);
    }

    /**
     * @param mixed $data
     * @return Timestamp[]
     */
    public function getTimestamps($data)
    {
        $timestamps = [];

        if (!empty($data['timeSpans'])) {
            foreach ($data['timeSpans'] as $index => $timeSpan) {
                if (!empty($timeSpan['start']) && !empty($timeSpan['end'])) {
                    $startDate = new \DateTime($timeSpan['start']);
                    $endDate = new \DateTime($timeSpan['end']);
                    $timestamps[] = new Timestamp($startDate, $endDate);
                }
            }
            ksort($timestamps);
        }

        return $timestamps;
    }

    /**
     * @param array $data
     *
     * @return OpeningHour[]
     */
    public function getOpeningHours($data)
    {
        $openingHours = [];

        if (!empty($data['openingHours'])) {
            foreach ($data['openingHours'] as $openingHour) {
                if (!empty($openingHour['dayOfWeek']) &&
                    !empty($openingHour['opens']) &&
                    !empty($openingHour['closes'])) {
                    $daysOfWeek = DayOfWeekCollection::deserialize($openingHour['dayOfWeek']);

                    $openingHours[] = new OpeningHour(
                        OpeningTime::fromNativeString($openingHour['opens']),
                        OpeningTime::fromNativeString($openingHour['closes']),
                        $daysOfWeek
                    );
                }
            }
        }

        return $openingHours;
    }
}
