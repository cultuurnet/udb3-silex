<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

class InMemoryDocumentRepository implements DocumentRepository
{
    /**
     * @var JsonDocument[]|string[]
     */
    private $documents;

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        if (!isset($this->documents[$id])) {
            throw DocumentDoesNotExist::withId($id);
        }

        return $this->documents[$id];
    }

    public function save(JsonDocument $readModel): void
    {
        $this->documents[$readModel->getId()] = $readModel;
    }

    public function remove($id): void
    {
        unset($this->documents[$id]);
    }
}
