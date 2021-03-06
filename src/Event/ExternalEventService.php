<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception as HttpException;
use Http\Client\HttpClient;

class ExternalEventService implements EventServiceInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * ExternalEventService constructor.
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getEvent(string $id): string
    {
        $uri = new Uri($id);
        $request = new Request('GET', $uri, ['Accept' => 'application/json']);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (HttpException $exception) {
            throw new EventNotFoundException();
        }

        if ($response->getStatusCode() !== 200) {
            throw new EventNotFoundException();
        }

        return (string) $response->getBody();
    }

    public function eventsOrganizedByOrganizer($organizerId)
    {
        throw new Exception('nope');
    }

    public function eventsLocatedAtPlace($placeId)
    {
        throw new Exception('nope');
    }
}
