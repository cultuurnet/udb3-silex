<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use League\Uri\Schemes\Http;

class HttpCalendarSummaryRepository implements CalendarSummaryRepositoryInterface
{
    /**
     * @var Http
     */
    protected $calendarSummariesLocation;

    /**
     * @var HttpClient
     */
    protected $httpClient;


    public function __construct(HttpClient $httpClient, Http $calendarSummariesLocation)
    {
        $this->httpClient = $httpClient;
        $this->calendarSummariesLocation = $calendarSummariesLocation;
    }

    public function get(string $offerId, ContentType $type, Format $format): string
    {
        $summaryLocation = $this->calendarSummariesLocation
            ->withPath('/events/' . $offerId . '/calsum')
            ->withQuery('format=' . $format->getValue());

        $summaryRequest = new Request(
            'GET',
            (string) $summaryLocation,
            [
                'Accept' => $type->getValue(),
            ]
        );

        try {
            return $this->httpClient
                ->sendRequest($summaryRequest)
                ->getBody()
                ->getContents();
        } catch (\Exception $exception) {
            throw new SummaryUnavailableException('No summary available for offer with id: ' . $offerId);
        }
    }
}
