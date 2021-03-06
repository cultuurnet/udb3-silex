<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;

class UpdateGeoCoordinatesCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var DefaultAddressFormatter
     */
    private $defaultAddressFormatter;

    /**
     * @var LocalityAddressFormatter
     */
    private $localityAddressFormatter;

    /**
     * @var GeocodingService|MockObject
     */
    private $geocodingService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateGeoCoordinatesFromAddressCommandHandler
    {
        $organizerRepository = new OrganizerRepository(
            $eventStore,
            $eventBus
        );

        $this->defaultAddressFormatter = new DefaultAddressFormatter();
        $this->localityAddressFormatter = new LocalityAddressFormatter();

        $this->geocodingService = $this->createMock(GeocodingService::class);

        return new UpdateGeoCoordinatesFromAddressCommandHandler(
            $organizerRepository,
            $this->defaultAddressFormatter,
            $this->localityAddressFormatter,
            $this->geocodingService
        );
    }

    /**
     * @test
     */
    public function it_creates_coordinates_from_an_address_and_updates_them_on_the_given_place()
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            new Title('some representative title'),
            [$address],
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $coordinates = $this->someCoordinates();

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with('Wetstraat 1, 1000 Bxl, BE')
            ->willReturn($coordinates);

        $expectedEvent = new GeoCoordinatesUpdated($organizerId, $coordinates);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_has_a_fallback_to_locality_when_full_address_has_null_coordinates()
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            new Title('some representative title'),
            [$address],
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $coordinates = $this->someCoordinates();

        $this->geocodingService->expects($this->exactly(2))
            ->method('getCoordinates')
            ->withConsecutive(
                [
                    'Wetstraat 1, 1000 Bxl, BE',
                ],
                [
                    '1000 Bxl, BE',
                ]
            )
            ->willReturnOnConsecutiveCalls(null, $coordinates);

        $expectedEvent = new GeoCoordinatesUpdated($organizerId, $coordinates);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([$expectedEvent]);
    }


    /**
     * @test
     */
    public function it_skips_update_if_the_geo_coordinates_can_not_be_resolved()
    {
        $organizerId = $this->aUuid();
        $address = $this->anAddress();

        $organizerCreated = new OrganizerCreated(
            $organizerId,
            new Title('some representative title'),
            [$address],
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $command = new UpdateGeoCoordinatesFromAddress($organizerId, $address);

        $this->geocodingService->expects($this->any())
            ->method('getCoordinates')
            ->willReturnOnConsecutiveCalls(null);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$organizerCreated])
            ->when($command)
            ->then([]);
    }


    public function aUuid(): string
    {
        return 'b9ec8a0a-ec9d-4dd3-9aaa-6d5b41b69d7c';
    }


    public function anAddress(): Address
    {
        return new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            Country::fromNative('BE')
        );
    }


    public function someCoordinates(): Coordinates
    {
        return new Coordinates(
            new Latitude(-0.12),
            new Longitude(4.76)
        );
    }
}
