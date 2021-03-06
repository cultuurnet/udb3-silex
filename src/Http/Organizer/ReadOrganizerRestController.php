<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;

class ReadOrganizerRestController
{
    use ApiProblemJsonResponseTrait;
    public const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';

    /**
     * @var EntityServiceInterface
     */
    private $service;

    /**
     * OrganizerController constructor.
     */
    public function __construct(
        EntityServiceInterface $service
    ) {
        $this->service = $service;
    }

    /**
     * Get an organizer by its cdbid.
     * @param string $cdbid
     * @return JsonLdResponse $response
     */
    public function get($cdbid)
    {
        $organizer = $this->service->getEntity($cdbid);

        if ($organizer) {
            $response = JsonLdResponse::create()
                ->setContent($organizer)
                ->setPublic();

            $response->headers->set('Vary', 'Origin');
        } else {
            $response = $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
        }

        return $response;
    }
}
