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

    public function getCalendarSummary($cdbid, Request $request): Response
    {
        $style = $request->query->get('style', 'text');
        $langCode = $request->query->get('langCode', 'nl_BE');
        $hidePastDates = $request->query->get('hidePast', false);
        $timeZone = $request->query->get('timeZone', 'Europe/Brussels');
        $format = $request->query->get('format', 'lg');

        $data = $this->documentRepository->fetch($cdbid, false);
        $place = Offer::fromJsonLd($data->getRawBody());

        if ($style !== 'html' && $style !== 'text') {
            return $this->createApiProblemJsonResponseNotFound('No style found for ' . $style, $cdbid);
        }

        if ($style === 'html') {
            $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
        } else {
            $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
        }

        return new Response($calSum->format($place, $format));
    }
}
