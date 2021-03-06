<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use ValueObjects\Enum\Enum;

/**
 * Class EventAdvantage
 *
 * @package CultuurNet\UDB3\EventExport\Format\HTML\Uitpas
 *
 * @method static $this POINT_COLLECTING()
 * @method static $this KANSENTARIEF()
 */
class EventAdvantage extends Enum
{
    public const POINT_COLLECTING = 'POINT_COLLECTING';
    public const KANSENTARIEF = 'KANSENTARIEF';
}
