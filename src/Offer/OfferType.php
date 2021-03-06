<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method static OfferType EVENT()
 * @method static OfferType PLACE()
 */
class OfferType extends Enum
{
    public const EVENT = 'Event';
    public const PLACE = 'Place';

    public static function fromCaseInsensitiveValue($value)
    {
        return self::fromNative(ucfirst(strtolower($value)));
    }
}
