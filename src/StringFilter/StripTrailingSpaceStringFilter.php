<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripTrailingSpaceStringFilter implements StringFilterInterface
{
    /**
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException('Argument should be string, got ' . gettype($string) . ' instead.');
        }

        return preg_replace('/[ \t]+$/m', '', $string);
    }
}
