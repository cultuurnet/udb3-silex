<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Crell\ApiProblem\ApiProblem;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 **/
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, $headers = [])
    {
        $headers += ['Content-Type' => 'application/problem+json'];

        $status = 400;
        if (null !== $problem->getStatus()) {
            $status = $problem->getStatus();
        }

        $data = $problem->asArray();

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }

    public static function notFound(string $message): self
    {
        $apiProblem = new ApiProblem($message);
        $apiProblem->setStatus(404);

        return new self($apiProblem);
    }
}
