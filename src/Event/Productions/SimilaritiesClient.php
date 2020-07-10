<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SimilaritiesClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $key;

    public function __construct(Client $client, string $uri, string $key)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->key = $key;
    }

    /**
     * @param SimilarEventPair[] $eventPairs
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function excludeTemporarily(array $eventPairs): void
    {
        $data['pairs'] = [];
        foreach ($eventPairs as $pair) {
            $data['pairs'][] = [
                'event1' => $pair->getEventOne(),
                'event2' => $pair->getEventTwo(),
            ];
        }

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/greylist?key=' . $this->key,
            ['json' => $data]
        );
    }

    public function excludePermanently(SimilarEventPair $pair)
    {
        $data['pairs'] = [
            'event1' => $pair->getEventOne(),
            'event2' => $pair->getEventTwo(),
        ];

        $response = $this->client->request(
            'PATCH',
            $this->uri . '/blacklist?key=' . $this->key,
            ['json' => $data]
        );
    }

    public function nextSuggestion(\DateTime $dateFrom, int $size = 1, int $offset = 0): Suggestion
    {
        try {
            $response = $this->client->request(
                'GET',
                $this->uri . '?size=' . $size . '&minDate=' .
                $dateFrom->format('Y-m-d') . '&offset' . $offset . '&key=' . $this->key
            );
        } catch (ClientException $throwable) {
            throw new SuggestionsNotFound();
        }
        $contents = json_decode($response->getBody()->getContents(), true);
        return new Suggestion($contents[0]['event1'], $contents[0]['event2']);
    }
}