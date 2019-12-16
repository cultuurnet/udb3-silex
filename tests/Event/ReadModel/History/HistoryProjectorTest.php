<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\Concluded;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

class HistoryProjectorTest extends TestCase
{
    private const EVENT_ID_1 = 'a0ee7b1c-a9c1-4da1-af7e-d15496014656';
    private const EVENT_ID_2 = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';

    private const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    /**
     * @var HistoryProjector
     */
    protected $historyProjector;

    /**
     * @var DocumentRepositoryInterface
     */
    protected $documentRepository;

    public function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->historyProjector = new HistoryProjector(
            $this->documentRepository
        );

        $eventImported = new EventImportedFromUDB2(
            self::EVENT_ID_1,
            $this->getEventCdbXml(self::EVENT_ID_1),
            self::CDBXML_NAMESPACE
        );

        $importedDate = '2015-03-04T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventImported->getEventId(),
            1,
            new Metadata(),
            $eventImported,
            DateTime::fromString($importedDate)
        );

        $this->historyProjector->handle($domainMessage);
    }

    /**
     * @param string $eventId
     * @return string
     */
    protected function getEventCdbXml($eventId)
    {
        return file_get_contents(__DIR__ . '/event-' . $eventId . '.xml');
    }

    /**
     * @test
     */
    public function it_logs_EventImportedFromUDB2()
    {
        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );

        $eventImported = new EventImportedFromUDB2(
            self::EVENT_ID_2,
            $this->getEventCdbXml(self::EVENT_ID_2),
            self::CDBXML_NAMESPACE
        );

        $importedDate = '2015-03-01T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventImported->getEventId(),
            1,
            new Metadata(),
            $eventImported,
            DateTime::fromString($importedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_2,
            [
                (object) [
                    'date' => '2015-03-01T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-09-08T09:10:16+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'info@traeghe.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_EventUpdatedFromUDB2()
    {
        $eventUpdated = new EventUpdatedFromUDB2(
            self::EVENT_ID_1,
            $this->getEventCdbXml(self::EVENT_ID_1),
            self::CDBXML_NAMESPACE
        );

        $updatedDate = '2015-03-25T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventUpdated->getEventId(),
            2,
            new Metadata(),
            $eventUpdated,
            DateTime::fromString($updatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'description' => 'Geüpdatet vanuit UDB2',
                    'date' => '2015-03-25T10:17:19+02:00',
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_creating_an_event()
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            new Title('Faith no More'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            new Metadata(
                [
                    'user_nick' => 'Jan Janssen',
                    'auth_api_key' => 'my-super-duper-key',
                    'api' => 'json-api',
                    'consumer' => [
                        'name' => 'My super duper name',
                    ]
                ]
            ),
            $eventCreated,
            DateTime::fromString($now->format(\DateTime::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) [
                    'date' => $now->format('c'),
                    'author' => 'Jan Janssen',
                    'description' => 'Aangemaakt in UiTdatabank',
                    'apiKey' => 'my-super-duper-key',
                    'api' => 'json-api',
                    'consumerName' => 'My super duper name',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_copying_an_event()
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';
        $originalEventId = '1fd05542-ce0b-4ed1-ad17-cf5a0f316da4';

        $eventCopied = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::PERMANENT())
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $eventCopied,
            DateTime::fromString($now->format(\DateTime::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) [
                    'date' => $now->format('c'),
                    'author' => 'Jan Janssen',
                    'description' => 'Event gekopieerd van ' . $originalEventId,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_titleTranslated()
    {
        $titleTranslated = new TitleTranslated(
            self::EVENT_ID_1,
            new Language('fr'),
            new Title('Titre en français')
        );

        $translatedDate = '2015-03-26T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $titleTranslated->getItemId(),
            3,
            new Metadata(['user_nick' => 'JohnDoe']),
            $titleTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-26T10:17:19+02:00',
                    'author' => 'JohnDoe',
                    'description' => 'Titel vertaald (fr)',
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_descriptionTranslated()
    {
        $descriptionTranslated = new DescriptionTranslated(
            self::EVENT_ID_1,
            new Language('fr'),
            new Description('Signalement en français')
        );

        $translatedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $descriptionTranslated->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $descriptionTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Beschrijving vertaald (fr)',
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_eventWasTagged()
    {
        $eventWasTagged = new LabelAdded(
            self::EVENT_ID_1,
            new Label('foo')
        );

        $taggedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventWasTagged->getItemId(),
            2,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $eventWasTagged,
            DateTime::fromString($taggedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'Jan Janssen',
                    'description' => "Label 'foo' toegepast",
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_tagErased()
    {
        $tagErased = new LabelRemoved(
            self::EVENT_ID_1,
            new Label('foo')
        );

        $tagErasedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $tagErased->getItemId(),
            2,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $tagErased,
            DateTime::fromString($tagErasedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'Jan Janssen',
                    'description' => "Label 'foo' verwijderd",
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Geïmporteerd vanuit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_approved(): void
    {
        $event = new Approved(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Goedgekeurd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_audience_updated(): void
    {
        $event = new AudienceUpdated(self::EVENT_ID_1, new Audience(AudienceType::EDUCATION()));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Toegang aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_booking_info_updated(): void
    {
        $event = new BookingInfoUpdated(self::EVENT_ID_1, new BookingInfo());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Reservatie-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_calendar_updated(): void
    {
        $event = new CalendarUpdated(self::EVENT_ID_1, new Calendar(CalendarType::PERMANENT()));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Kalender-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_concluded(): void
    {
        $event = new Concluded(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Event afgelopen',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_contact_point_updated(): void
    {
        $event = new ContactPointUpdated(self::EVENT_ID_1, new ContactPoint());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Contact-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_description_updated(): void
    {
        $event = new DescriptionUpdated(self::EVENT_ID_1, new Description('new'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Beschrijving aangepast',
                ],
            ]
        );
    }

    protected function assertHistoryContainsLogs(string $eventId, array $history): void
    {
        /** @var JsonDocument $document */
        $document = $this->documentRepository->get($eventId);
        $body = array_values((array) $document->getBody());

        $body = array_map(function (\stdClass $log) {
            return (array) $log;
        }, $body);

        foreach ($history as $log) {
            $this->assertContains((array) $log, $body);
        }
    }

    /**
     * @param string $userNick
     * @param string $consumerName
     * @return Metadata
     */
    protected function entryApiMetadata($userNick, $consumerName)
    {
        $values = [
            'user_nick' => $userNick,
            'consumer' => [
                'name' => $consumerName,
            ],
        ];

        return new Metadata($values);
    }
}
