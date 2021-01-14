<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Timestamp;

class CalendarJSONParser
{
    public function getStartDate(array $data): ?\DateTimeInterface
    {
        if (!isset($data['startDate'])) {
            return null;
        }

        return new \DateTime($data['startDate']);
    }

    public function getEndDate(array $data): ?\DateTimeInterface
    {
        if (!isset($data['endDate'])) {
            return null;
        }

        return new \DateTime($data['endDate']);
    }

    public function getStatus(array $data): ?Status
    {
        if (!isset($data['status'])) {
            return null;
        }

        return Status::deserialize($data['status']);
    }

    /**
     * @return Timestamp[]
     */
    public function getTimestamps(array $data): array
    {
        if (empty($data['timeSpans'])) {
            return [];
        }

        $timestamps = [];
        foreach ($data['timeSpans'] as $index => $timeSpan) {
            if (empty($timeSpan['start']) || empty($timeSpan['end'])) {
                continue;
            }

            $timestamp = new Timestamp(new \DateTime($timeSpan['start']), new \DateTime($timeSpan['end']));

            if (isset($timeSpan['status'])) {
                $timestamp = $timestamp->withStatus(Status::deserialize($timeSpan['status']));
            }

            $timestamps[] = $timestamp;
        }

        ksort($timestamps);

        return $timestamps;
    }

    /**
     * @return OpeningHour[]
     */
    public function getOpeningHours(array $data): array
    {
        if (empty($data['openingHours'])) {
            return [];
        }

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
