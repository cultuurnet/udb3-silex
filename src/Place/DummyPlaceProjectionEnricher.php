<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class DummyPlaceProjectionEnricher implements DocumentRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @param DocumentRepositoryInterface $repository
     */
    public function __construct(DocumentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function get($id)
    {
        return $this->repository->get($id);
    }

    public function save(JsonDocument $readModel)
    {
        $this->repository->save($readModel);
    }

    public function remove($id)
    {
        $this->repository->remove($id);
    }
}
