<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;

class UpdateStatusHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ): CommandHandlerInterface {
        $repository = new EventRepository(
            $eventStore,
            $eventBus
        );

        return new UpdateStatusHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_handle_update_status_for_permanent_event(): void
    {
        $id = '1';
        $initialCalendar = new Calendar(CalendarType::PERMANENT());

        $newStatus = new Status(StatusType::temporarilyUnavailable(), []);
        $expectedCalendar = (new Calendar(CalendarType::PERMANENT()))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_will_update_status_of_sub_events(): void
    {
        $id = '1';
        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-12-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-12-24');

        $initialTimestamps = [new Timestamp($startDate, $endDate)];
        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);

        $newStatus = new Status(StatusType::unavailable(), []);

        $expectedTimestamps = [new Timestamp($startDate, $endDate, new Status(StatusType::unavailable(), []))];
        $expectedCalendar = (new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    private function getEventCreated(string $id, Calendar $calendar): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            $calendar
        );
    }
}
