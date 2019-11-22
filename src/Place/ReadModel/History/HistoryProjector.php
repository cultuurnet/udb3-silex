<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\ReadModel\Enum\EventDescription;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;
use DateTimeZone;

final class HistoryProjector implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof PlaceCreated:
                $this->projectPlaceCreated($event, $domainMessage);
                break;
            case $event instanceof PlaceDeleted:
                $this->projectPlaceDeleted($event, $domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($event, $domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($event, $domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($event, $domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($event, $domainMessage);
                break;
            case $event instanceof PlaceImportedFromUDB2:
                $this->projectPlaceImportedFromUDB2($event, $domainMessage);
                break;
            case $event instanceof PlaceUpdatedFromUDB2:
                $this->projectPlaceUpdatedFromUDB2($event, $domainMessage);
                break;
        }
    }

    private function projectPlaceCreated(PlaceCreated $event, DomainMessage $domainMessage): void
    {
        $this->project($event->getPlaceId(), 'Aangemaakt in UiTdatabank', $domainMessage);
    }

    private function projectPlaceDeleted(PlaceDeleted $event, DomainMessage $domainMessage): void
    {
        $this->project($event->getItemId(), EventDescription::DELETED, $domainMessage);
    }

    private function projectLabelAdded(LabelAdded $event, DomainMessage $domainMessage): void
    {
        $this->project($event->getItemId(), "Label '{$event->getLabel()}' toegepast", $domainMessage);
    }

    private function projectLabelRemoved(LabelRemoved $event, DomainMessage $domainMessage): void
    {
        $this->project($event->getItemId(), "Label '{$event->getLabel()}' verwijderd", $domainMessage);
    }

    private function projectDescriptionTranslated(DescriptionTranslated $event, DomainMessage $domainMessage)
    {
        $this->project($event->getItemId(), "Beschrijving vertaald ({$event->getLanguage()})", $domainMessage);
    }

    private function projectTitleTranslated(TitleTranslated $event, DomainMessage $domainMessage)
    {
        $this->project($event->getItemId(), "Titel vertaald ({$event->getLanguage()})", $domainMessage);
    }

    private function projectPlaceImportedFromUDB2(PlaceImportedFromUDB2 $event, DomainMessage $domainMessage)
    {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $this->projectLog(
            $event->getActorId(),
            new Log(
                DateTime::createFromFormat(
                    'Y-m-d?H:i:s',
                    $udb2Event->getCreationDate(),
                    new DateTimeZone('Europe/Brussels')
                ),
                'Aangemaakt in UDB2',
                $udb2Event->getCreatedBy()
            )
        );

        $this->project($event->getActorId(), 'Geïmporteerd vanuit UDB2', $domainMessage);
    }

    private function projectPlaceUpdatedFromUDB2(PlaceUpdatedFromUDB2 $event, DomainMessage $domainMessage)
    {
        $this->project($event->getActorId(), 'Aangemaakt in UDB2', $domainMessage);
    }

    private function project(string $eventId, string $description, DomainMessage $domainMessage)
    {
        $this->projectLog(
            $eventId,
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                $description,
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function domainMessageDateToNativeDate(BroadwayDateTime $date): DateTime
    {
        $dateString = $date->toString();
        return DateTime::createFromFormat(
            BroadwayDateTime::FORMAT_STRING,
            $dateString
        );
    }

    private function projectLog(string $eventId, Log $log): void
    {
        $historyDocument = $this->loadDocumentFromRepositoryByEventId($eventId);

        $history = $historyDocument->getBody();

        // Append most recent one to the top.
        array_unshift($history, $log);

        $this->documentRepository->save(
            $historyDocument->withBody($history)
        );
    }

    private function loadDocumentFromRepositoryByEventId(string $eventId): JsonDocument
    {
        $historyDocument = $this->documentRepository->get($eventId);

        if (!$historyDocument) {
            $historyDocument = new JsonDocument($eventId, '[]');
        }

        return $historyDocument;
    }

    private function getAuthorFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['user_nick'])) {
            return (string) $properties['user_nick'];
        }

        return null;
    }

    private function getConsumerFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['consumer']['name'])) {
            return (string) $properties['consumer']['name'];
        }

        return null;
    }

    private function getApiKeyFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['auth_api_key'])) {
            return $properties['auth_api_key'];
        }

        return null;
    }

    private function getApiFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['api'])) {
            return $properties['api'];
        }

        return null;
    }
}
