<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\Serializable;
use InvalidArgumentException;

final class BookingAvailability implements Serializable
{
    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';

    /**
     * @var string
     */
    private $value;

    /**
     * @var string[]
     */
    private const ALLOWED_VALUES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
    ];

    private function __construct(string $value)
    {
        if (!\in_array($value, self::ALLOWED_VALUES, true)) {
            throw new InvalidArgumentException('Booking availability does not support the value "' . $value . '"');
        }
        $this->value = $value;
    }

    public static function available(): BookingAvailability
    {
        return new BookingAvailability(self::AVAILABLE);
    }

    public static function unavailable(): BookingAvailability
    {
        return new BookingAvailability(self::UNAVAILABLE);
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public static function fromNative(string $value): BookingAvailability
    {
        return new BookingAvailability($value);
    }

    public function equals(BookingAvailability $status): bool
    {
        return $this->value === $status->toNative();
    }

    public static function deserialize(array $data): BookingAvailability
    {
        return new BookingAvailability($data['type']);
    }

    public function serialize(): array
    {
        return [
            'type' => $this->value,
        ];
    }
}
