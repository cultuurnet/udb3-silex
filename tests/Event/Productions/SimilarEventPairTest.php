<?php

namespace CultuurNet\UDB3\Event\Productions;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class SimilarEventPairTest extends TestCase
{
    /**
     * @test
     */
    public function itCanBeCreatedFromValues(): void
    {
        $eventOne = Uuid::uuid4()->toString();
        $eventTwo = Uuid::uuid4()->toString();
        $eventPair = new SimilarEventPair($eventOne, $eventTwo);
        $this->assertEquals($eventOne, $eventPair->getEventOne());
        $this->assertEquals($eventTwo, $eventPair->getEventTwo());
    }

    /**
     * @test
     */
    public function itCanBeCreatedFromArray(): void
    {
        $eventOne = Uuid::uuid4()->toString();
        $eventTwo = Uuid::uuid4()->toString();
        $eventPair = SimilarEventPair::fromArray([$eventOne, $eventTwo]);
        $this->assertEquals($eventOne, $eventPair->getEventOne());
        $this->assertEquals($eventTwo, $eventPair->getEventTwo());
    }
}
