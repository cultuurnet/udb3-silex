<?php

namespace CultuurNet\UDB3\UDB2\Event;

use Broadway\EventHandling\EventListenerInterface;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\Cdb\Event\SpecificationInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Media\Properties\UnsupportedMIMETypeException;
use CultuurNet\UDB3\UDB2\Event\Events\EventCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Event\Events\EventUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Label\LabelApplierInterface;
use CultuurNet\UDB3\UDB2\Media\MediaImporter;
use CultuurNet\UDB3\UDB2\OfferAlreadyImportedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Applies incoming UDB2 events enriched with cdb xml towards UDB3 Offer.
 *
 * Wether the UDB2 CdbXML event should be processed is defined by an
 * implementation of SpecificationInterface.
 */
class EventImporter implements EventListenerInterface, LoggerAwareInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;
    use LoggerAwareTrait;

    /**
     * @var SpecificationInterface
     */
    protected $offerSpecification;

    /**
     * @var MediaImporter
     */
    protected $mediaImporter;

    /**
     * @var RepositoryInterface
     */
    protected $eventRepository;

    /**
     * @var LabelApplierInterface
     */
    private $labelApplier;

    public function __construct(
        SpecificationInterface $offerSpecification,
        RepositoryInterface $eventRepository,
        MediaImporter $mediaImporter,
        LabelApplierInterface $labelApplier
    ) {
        $this->offerSpecification = $offerSpecification;
        $this->eventRepository = $eventRepository;
        $this->mediaImporter = $mediaImporter;
        $this->labelApplier = $labelApplier;

        $this->logger = new NullLogger();
    }

    /**
     * @param CultureFeed_Cdb_Item_Event $event
     * @return bool
     */
    private function isSatisfiedBy(CultureFeed_Cdb_Item_Event $event)
    {
        return $this->offerSpecification->isSatisfiedByEvent($event);
    }

    protected function applyEventCreatedEnrichedWithCdbXml(
        EventCreatedEnrichedWithCdbXml $eventCreated
    ): void {
        $cdbXmlEvent = EventItemFactory::createEventFromCdbXml(
            (string) $eventCreated->getCdbXmlNamespaceUri(),
            (string) $eventCreated->getCdbXml()
        );

        if (!$this->isSatisfiedBy($cdbXmlEvent)) {
            $this->logger->debug(
                'UDB2 event does not satisfy the criteria',
                [
                    'offer-id' => $cdbXmlEvent->getCdbId(),
                ]
            );
            return;
        }

        $this->createWithUpdateFallback(
            new StringLiteral($cdbXmlEvent->getCdbId()),
            $eventCreated
        );
    }

    protected function applyEventUpdatedEnrichedWithCdbXml(
        EventUpdatedEnrichedWithCdbXml $eventUpdated
    ): void {
        $cdbXmlEvent = EventItemFactory::createEventFromCdbXml(
            (string) $eventUpdated->getCdbXmlNamespaceUri(),
            (string) $eventUpdated->getCdbXml()
        );

        if (!$this->isSatisfiedBy($cdbXmlEvent)) {
            $this->logger->debug('UDB2 event does not satisfy the criteria');
            return;
        }

        $this->updateWithCreateFallback(
            new StringLiteral($cdbXmlEvent->getCdbId()),
            $eventUpdated
        );
    }

    private function updateWithCreateFallback(
        StringLiteral $entityId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        try {
            $this->update($entityId, $cdbXml);

            $this->logger->info(
                'Offer succesfully updated.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );
        } catch (AggregateNotFoundException $e) {
            $this->logger->debug(
                'Update failed because offer did not exist yet, trying to create it as a fallback.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );

            $this->create($entityId, $cdbXml);

            $this->logger->info(
                'Offer succesfully created.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );
        }
    }

    private function createWithUpdateFallback(
        StringLiteral $entityId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        try {
            $this->create($entityId, $cdbXml);

            $this->logger->info(
                'Offer succesfully created.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );
        } catch (OfferAlreadyImportedException $e) {
            $this->logger->debug(
                'An offer with the same id already exists, trying to update as a fallback.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );

            $this->update($entityId, $cdbXml);

            $this->logger->info(
                'Offer succesfully updated.',
                [
                    'offer-id' => (string) $entityId,
                ]
            );
        }
    }

    private function update(
        StringLiteral $eventId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        /** @var UpdateableWithCdbXmlInterface|Event $udb3Event */
        $udb3Event = $this->eventRepository->load((string) $eventId);

        $udb3Event->updateWithCdbXml(
            $cdbXml->getCdbXml(),
            $cdbXml->getCdbXmlNamespaceUri()
        );

        $cdbEvent = EventItemFactory::createEventFromCdbXml(
            $cdbXml->getCdbXmlNamespaceUri(),
            $cdbXml->getCdbXml()
        );

        $imageCollection = $this->mediaImporter->importImages($cdbEvent);
        $udb3Event->updateImagesFromUDB2($imageCollection);

        $this->labelApplier->apply($udb3Event);

        $locationId = new LocationId($cdbEvent->getLocation()->getCdbid());
        if ($locationId->isDummyPlaceForEducation()) {
            $udb3Event->updateAudience(new Audience(AudienceType::EDUCATION()));
        }

        $this->eventRepository->save($udb3Event);
    }

    private function create(
        StringLiteral $eventId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        try {
            $this->eventRepository->load((string) $eventId);
            throw new OfferAlreadyImportedException('An offer with id: ' . $eventId . 'was already imported.');
        } catch (AggregateNotFoundException $e) {
            $this->logger->info(
                'No existing offer with the same id found so it is safe to import.',
                [
                    'offer-id' => (string) $eventId,
                ]
            );
        }

        $udb3Event = Event::importFromUDB2(
            $eventId,
            $cdbXml->getCdbXml(),
            $cdbXml->getCdbXmlNamespaceUri()
        );

        $cdbEvent = EventItemFactory::createEventFromCdbXml(
            $cdbXml->getCdbXmlNamespaceUri(),
            $cdbXml->getCdbXml()
        );

        try {
            $imageCollection = $this->mediaImporter->importImages($cdbEvent);
            if ($imageCollection->length() > 0) {
                $udb3Event->importImagesFromUDB2($imageCollection);
            }
        } catch (UnsupportedMIMETypeException $e) {
            $this->logger->error(
                'Unable to import images for offer. ' . $e->getMessage(),
                ['offer-id' => (string) $eventId]
            );
        };

        $locationId = new LocationId($cdbEvent->getLocation()->getCdbid());
        if ($locationId->isDummyPlaceForEducation()) {
            $udb3Event->updateAudience(new Audience(AudienceType::EDUCATION()));
        }

        $this->eventRepository->save($udb3Event);
    }
}
