<?php

namespace CultuurNet\UDB3\UDB2\Label;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class LabelImporter implements EventListener, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var LabelServiceInterface
     */
    private $labelService;


    public function __construct(
        LabelServiceInterface $labelService
    ) {
        $this->labelService = $labelService;
        $this->logger = new NullLogger();
    }


    public function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ) {
        $event = EventItemFactory::createEventFromCdbXml(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($event);
    }


    public function applyPlaceImportedFromUDB2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ) {
        $place = ActorItemFactory::createActorFromCdbXml(
            $placeImportedFromUDB2->getCdbXmlNamespaceUri(),
            $placeImportedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($place);
    }


    public function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImportedFromUDB2
    ) {
        $organizer = ActorItemFactory::createActorFromCdbXml(
            $organizerImportedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerImportedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($organizer);
    }


    public function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ) {
        $event = EventItemFactory::createEventFromCdbXml(
            $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $eventUpdatedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($event);
    }


    public function applyPlaceUpdatedFromUDB2(
        PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
    ) {
        $place = ActorItemFactory::createActorFromCdbXml(
            $placeUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $placeUpdatedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($place);
    }


    public function applyOrganizerUpdatedFromUDB2(
        OrganizerUpdatedFromUDB2 $organizerUpdatedFromUDB2
    ) {
        $organizer = ActorItemFactory::createActorFromCdbXml(
            $organizerUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerUpdatedFromUDB2->getCdbXml()
        );

        $this->createLabelAggregatesFromCdbItem($organizer);
    }


    private function createLabelAggregatesFromCdbItem(\CultureFeed_Cdb_Item_Base $cdbItem)
    {
        $labelCollection = LabelCollection::fromKeywords(
            $cdbItem->getKeywords(true)
        );

        foreach ($labelCollection->asArray() as $label) {
            $this->labelService->createLabelAggregateIfNew(
                new LabelName((string) $label),
                $label->isVisible()
            );
        }
    }
}
