<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

interface TabularDataFileWriterInterface
{
    /**
     * @param string[] $row
     */
    public function writeRow($row);

    public function close();
}
