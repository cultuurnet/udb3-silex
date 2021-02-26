<?php

declare(strict_types=1);
/**
 * @file
 */

namespace CultuurNet\UDB3\Doctrine\DBAL;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

interface SchemaConfiguratorInterface
{
    public function configure(AbstractSchemaManager $schemaManager);
}
