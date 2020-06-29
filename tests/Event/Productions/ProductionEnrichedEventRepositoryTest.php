<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class ProductionEnrichedEventRepositoryTest extends TestCase
{
    /**
     * @var ProductionEnrichedEventRepository
     */
    private $productionEnrichedEventRepository;

    /**
     * @var ProductionRepository | MockObject
     */
    private $productionRepository;

    /**
     * @var DocumentRepositoryInterface | MockObject
     */
    private $eventRepository;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->productionRepository = $this->createMock(ProductionRepository::class);

        $this->productionEnrichedEventRepository = new ProductionEnrichedEventRepository(
            $this->eventRepository,
            $this->productionRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_a_null_production_when_the_event_does_not_belong_to_a_production(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $originalJsonDocument = new JsonDocument(
            $eventId,
            json_encode((object) ['@id' => $eventId])
        );


        $this->eventRepository->method('get')->willReturn($originalJsonDocument);
        $this->productionRepository->method('findProductionForEventId')->willThrowException(
            new EntityNotFoundException()
        );

        $actual = $this->productionEnrichedEventRepository->get($eventId);

        $this->assertEquals($eventId, $actual->getBody()->{'@id'});
        $this->assertNull($actual->getBody()->production);
    }

    /**
     * @test
     */
    public function it_returns_production_data_when_event_belongs_to_a_production(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $originalJsonDocument = new JsonDocument(
            $eventId,
            json_encode((object) ['@id' => $eventId])
        );

        $otherEventId = Uuid::uuid4()->toString();
        $productionId = ProductionId::generate();
        $productionName = 'The Teenage Mutant Ninja String Quartet in Concert - Heroes in a half Cello';
        $production = new Production(
            $productionId,
            $productionName,
            [
                $eventId,
                $otherEventId,
            ]
        );

        $this->eventRepository->method('get')->willReturn($originalJsonDocument);
        $this->productionRepository->method('findProductionForEventId')->willReturn($production);

        $actual = $this->productionEnrichedEventRepository->get($eventId);

        $this->assertEquals($eventId, $actual->getBody()->{'@id'});
        $this->assertEquals($productionId->toNative(), $actual->getBody()->production->id);
        $this->assertEquals($productionName, $actual->getBody()->production->title);
        $this->assertEquals([$otherEventId], $actual->getBody()->production->otherEvents);
    }
}