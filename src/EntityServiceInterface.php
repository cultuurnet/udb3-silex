<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

/**
 * Interface for a service performing entity related tasks.
 */
interface EntityServiceInterface
{
    public function getEntity(string $id): string;

    public function iri($id);
}
