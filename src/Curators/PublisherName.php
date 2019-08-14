<?php

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;

final class PublisherName
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        if (!self::isValid($name)) {
            throw new InvalidArgumentException('Invalid publisher: ' . $name);
        }
        $this->name = $name;
    }

    private static function isValid(string $name): bool
    {
        return !empty($name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
