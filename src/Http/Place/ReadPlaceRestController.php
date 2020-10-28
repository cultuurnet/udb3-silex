<?php

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\SearchV3\ValueObjects\Place;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExistException;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadPlaceRestController
{
    private const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';

    use ApiProblemJsonResponseTrait;

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;


    public function __construct(
        DocumentRepository $documentRepository,
        SerializerInterface $serializer
    ) {
        $this->documentRepository = $documentRepository;
        $this->serializer = $serializer;
    }

    public function get(string $cdbid, Request $request): JsonResponse
    {
        $includeMetadata = $request->query->get('includeMetadata', false);

        try {
            $place = $this->documentRepository->fetch($cdbid, $includeMetadata);
        } catch (DocumentDoesNotExistException $e) {
            return $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
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
        $place = $this->serializer->deserialize($data->getRawBody(), Place::class);

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
