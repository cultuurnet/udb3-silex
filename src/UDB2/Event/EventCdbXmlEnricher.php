<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use CultureFeed_Cdb_Xml;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\UDB2\DomainEvents\EventCreated;
use CultuurNet\UDB3\UDB2\DomainEvents\EventUpdated;
use CultuurNet\UDB3\UDB2\Event\Events\EventCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Event\Events\EventUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\XML\XMLValidationException;
use CultuurNet\UDB3\UDB2\XML\XMLValidationServiceInterface;
use DOMDocument;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;
use XMLReader;

/**
 * Republishes incoming UDB2 events enriched with their cdbxml.
 */
class EventCdbXmlEnricher implements EventListener, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var XMLValidationServiceInterface|null
     */
    protected $xmlValidationService;

    /**
     * @var StringLiteral
     */
    protected $cdbXmlNamespaceUri;

    public function __construct(
        EventBus $eventBus,
        HttpClient $httpClient,
        XMLValidationServiceInterface $xmlValidationService = null
    ) {
        $this->eventBus = $eventBus;
        $this->httpClient = $httpClient;
        $this->xmlValidationService = $xmlValidationService;
        $this->cdbXmlNamespaceUri = new StringLiteral(
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );
        $this->logger = new NullLogger();
    }

    protected function applyEventUpdated(
        EventUpdated $eventUpdated,
        DomainMessage $message
    ) {
        $xml = $this->retrieveXml($eventUpdated->getUrl());

        $enrichedEventUpdated = EventUpdatedEnrichedWithCdbXml::fromEventUpdated(
            $eventUpdated,
            $xml,
            $this->cdbXmlNamespaceUri
        );

        $this->publish(
            $enrichedEventUpdated,
            $message->getMetadata()
        );
    }

    protected function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $message
    ) {
        $xml = $this->retrieveXml($eventCreated->getUrl());

        $enrichedEventCreated = EventCreatedEnrichedWithCdbXml::fromEventCreated(
            $eventCreated,
            $xml,
            $this->cdbXmlNamespaceUri
        );

        $this->publish(
            $enrichedEventCreated,
            $message->getMetadata()
        );
    }

    /**
     * @return StringLiteral
     * @throws EventNotFoundException
     * @throws XMLValidationException
     */
    private function retrieveXml(Url $url)
    {
        $response = $this->internalSendRequest($url);

        $xml = $response->getBody()->getContents();

        $this->guardValidXml($xml);

        $eventXml = $this->extractEventElement($xml);

        return new StringLiteral($eventXml);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @throws EventNotFoundException
     */
    private function internalSendRequest(Url $url)
    {
        $this->logger->debug('retrieving cdbxml from ' . (string) $url);

        $request = new Request(
            'GET',
            (string) $url,
            [
                'Accept' => 'application/xml',
            ]
        );

        $startTime = microtime(true);

        $response = $this->httpClient->sendRequest($request);

        $delta = round(microtime(true) - $startTime, 3) * 1000;
        $this->logger->debug('sendRequest took ' . $delta . ' ms.');

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                'unable to retrieve cdbxml, server responded with ' .
                $response->getStatusCode() . ' ' . $response->getReasonPhrase()
            );

            throw new EventNotFoundException(
                'Unable to retrieve event from ' . (string) $url
            );
        }

        $this->logger->debug('retrieved cdbxml');

        return $response;
    }

    /**
     * @param object $payload
     */
    private function publish($payload, Metadata $metadata)
    {
        $message = new DomainMessage(
            UUID::generateAsString(),
            1,
            $metadata,
            $payload,
            DateTime::now()
        );

        $domainEventStream = new DomainEventStream([$message]);
        $this->eventBus->publish($domainEventStream);
    }

    /**
     * @param string $cdbXml
     * @return string
     * @throws \RuntimeException
     */
    private function extractEventElement($cdbXml)
    {
        $reader = new XMLReader();
        $reader->xml($cdbXml);

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case ($reader::ELEMENT):
                    if ($reader->localName === 'event') {
                        $this->logger->debug('found event in cdbxml');

                        $node = $reader->expand();
                        $dom = new DOMDocument('1.0');
                        $n = $dom->importNode($node, true);
                        $dom->appendChild($n);
                        return $dom->saveXML();
                    }
            }
        }

        $this->logger->error('no event found in cdbxml!');

        throw new \RuntimeException(
            'Event could not be found in the Entry API response body.'
        );
    }

    /**
     * @param string $xml
     */
    private function guardValidXml($xml)
    {
        if ($this->xmlValidationService) {
            $xmlErrors = $this->xmlValidationService->validate($xml);
            if (!empty($xmlErrors)) {
                $exception = XMLValidationException::fromXMLValidationErrors($xmlErrors);
                $this->logger->error(
                    'cdbxml is invalid!',
                    ['errors' => $exception->getMessage()]
                );
                throw $exception;
            }

            $this->logger->debug('cdbxml is valid');
        }
    }
}
