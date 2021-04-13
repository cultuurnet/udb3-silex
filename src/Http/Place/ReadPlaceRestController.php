<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;

class ReadPlaceRestController
{
    use ApiProblemJsonResponseTrait;
    private const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        DocumentRepository $documentRepository
    ) {
        $this->documentRepository = $documentRepository;
    }

    public function get(ServerRequestInterface $request): JsonResponse
    {
        $placeId = $request->getAttribute('cdbid');
        $queryParams = $request->getQueryParams();
        $includeMetadata = (bool) $queryParams['includeMetadata'];

        try {
            $place = $this->documentRepository->fetch($placeId, $includeMetadata);
        } catch (DocumentDoesNotExist $e) {
            return ApiProblemJsonResponse::notFound(sprintf(self::GET_ERROR_NOT_FOUND, $placeId));
        }

        return new JsonLdResponse($place->getRawBody(), 200, ['Vary' => 'Origin']);
    }

    public function getCalendarSummary(ServerRequestInterface $request): Response
    {
        $placeId = $request->getAttribute('cdbid');
        $queryParams = $request->getQueryParams();

        $style = $queryParams['style'] ?? 'text';
        $langCode = $queryParams['langCode'] ?? 'nl_BE';
        $hidePastDates = $queryParams['hidePast'] ?? false;
        $timeZone = $queryParams['timeZone'] ?? 'Europe/Brussels';
        $format = $queryParams['format'] ?? 'lg';

        switch ($style) {
            case 'html':
                $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
                break;
            case 'text':
                $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
                break;
            default:
                return ApiProblemJsonResponse::notFound("No $style calendar summary found for place with id $placeId");
        }

        $data = $this->documentRepository->fetch($placeId, false);
        $place = Offer::fromJsonLd($data->getRawBody());

        return new Response($calSum->format($place, $format));
    }
}
