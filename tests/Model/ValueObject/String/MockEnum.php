<?php

namespace CultuurNet\UDB3\Model\ValueObject\String;

/**
 * @method static MockEnum foo
 * @method static MockEnum bar
 * @phpstan-ignore-next-line
 */
class MockEnum extends Enum
{
    /**
     * @return string[]
     */
    public static function getAllowedValues()
    {
        return [
            'foo',
            'bar',
        ];
    }
}
