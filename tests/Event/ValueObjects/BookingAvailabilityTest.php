<?php

declare(strict_types=1);

namespace Event\ValueObjects;

use CultuurNet\UDB3\Event\ValueObjects\BookingAvailability;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            ['type' => 'Available'],
            BookingAvailability::available()->serialize()
        );

        $this->assertEquals(
            ['type' => 'Unavailable'],
            BookingAvailability::unavailable()->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->assertEquals(
            BookingAvailability::fromNative('Available'),
            BookingAvailability::deserialize(['type' => 'Available'])
        );

        $this->assertEquals(
            BookingAvailability::fromNative('Unavailable'),
            BookingAvailability::deserialize(['type' => 'Unavailable'])
        );
    }

    /**
     * @test
     */
    public function it_is_comparable(): void
    {
        $this->assertTrue(
            BookingAvailability::available()->equals(BookingAvailability::fromNative('Available'))
        );

        $this->assertFalse(
            BookingAvailability::fromNative('Unavailable')->equals(BookingAvailability::available())
        );
    }
}
