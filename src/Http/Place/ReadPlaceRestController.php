<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            return $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $placeId);
        }

        $response = JsonLdResponse::create()
            ->setContent($place->getRawBody());

        $response->headers->set('Vary', 'Origin');

        return $response;
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

        if ($style !== 'html' && $style !== 'text') {
            return $this->createApiProblemJsonResponseNotFound('No style found for ' . $style, $placeId);
        }

        if ($style === 'html') {
            $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
        } else {
            $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
        }

        $data = $this->documentRepository->fetch($placeId, false);
        $place = Offer::fromJsonLd($data->getRawBody());

        return new Response($calSum->format($place, $format));
    }
}
