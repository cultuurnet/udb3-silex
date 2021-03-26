<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

class OfferMetadataProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var OfferMetadataRepository
     */
    private $repository;

    /**
     * @var array<string,string>
     */
    private $apiKeyConsumerMapping;

    public function __construct(
        OfferMetadataRepository $repository,
        array $apiKeyConsumerMapping
    ) {
        $this->repository = $repository;
        $this->apiKeyConsumerMapping = $apiKeyConsumerMapping;
    }

    public function applyEventCreated(EventCreated $eventCreated, DomainMessage $domainMessage)
    {
        try {
            $offerMetadata = $this->repository->get($eventCreated->getEventId());
        } catch (EntityNotFoundException $e) {
            $offerMetadata = OfferMetadata::default($eventCreated->getEventId());
        }

        $createdByApiConsumer = $this->getCreatedByApiConsumerFromMetadata($domainMessage->getMetadata());
        $offerMetadata = $offerMetadata->withCreatedByApiConsumer($createdByApiConsumer);

        $this->repository->save($offerMetadata);
    }

    private function getCreatedByApiConsumerFromMetadata(Metadata $metadata): string
    {
        $properties = $metadata->serialize();

        if (!isset($properties['auth_api_key'])) {
            return 'unknown';
        }

        $apiKey = $properties['auth_api_key'];
        if (!array_key_exists($apiKey, $this->apiKeyConsumerMapping)) {
            return 'other';
        }

        return $this->apiKeyConsumerMapping[$apiKey];
    }
}
