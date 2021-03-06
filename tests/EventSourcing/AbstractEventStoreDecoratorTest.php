<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\EventStore\EventStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractEventStoreDecoratorTest extends TestCase
{
    /**
     * @var EventStore|MockObject
     */
    private $eventStore;

    /**
     * @var AbstractEventStoreDecorator|MockObject
     */
    private $abstractEventStoreDecorator;

    protected function setUp()
    {
        $this->eventStore = $this->createMock(EventStore::class);

        $this->abstractEventStoreDecorator = $this->getMockForAbstractClass(
            AbstractEventStoreDecorator::class,
            [$this->eventStore]
        );
    }

    /**
     * @test
     */
    public function it_calls_load_on_event_store()
    {
        $id = 'id';

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($id);

        $this->abstractEventStoreDecorator->load($id);
    }

    /**
     * @test
     */
    public function it_returns_domain_event_stream_from_load()
    {
        $id = '$id';
        $expectedStream = new DomainEventStream(['a', 'b']);

        $this->eventStore->method('load')
            ->with($id)
            ->willReturn($expectedStream);

        $stream = $this->abstractEventStoreDecorator->load($id);

        $this->assertEquals($expectedStream, $stream);
    }

    /**
     * @test
     */
    public function it_calls_append_on_event_store()
    {
        $id = 'id';
        /** @var DomainEventStream $eventStream */
        $eventStream = $this->createMock(DomainEventStream::class);

        $this->eventStore->expects($this->once())
            ->method('append')
            ->with($id, $eventStream);

        $this->abstractEventStoreDecorator->append($id, $eventStream);
    }
}
