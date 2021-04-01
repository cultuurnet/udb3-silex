<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Broadway\Repository\AggregateNotFoundException;
use Crell\ApiProblem\ApiProblem;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\CalendarSummaryV3\FormatterException;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\EventCannotBeAddedToProduction;
use CultuurNet\UDB3\Event\Productions\EventCannotBeRemovedFromProduction;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Jwt\JwtParserException;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation\EventHasTicketSalesException;
use Error;
use Exception;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;

class WebErrorHandlerProvider implements ServiceProviderInterface
{
    private static $debug = false;

    private const BAD_REQUESTS = [
        EntityNotFoundException::class,
        CommandAuthorizationException::class,
        NotFoundHttpException::class,
        MethodNotAllowedException::class,
        DataValidationException::class,
        GroupedValidationException::class,
        RequestAuthenticationException::class,
        MissingValueException::class,
        AggregateNotFoundException::class,
        MethodNotAllowedHttpException::class,
        EventHasTicketSalesException::class,
        MediaObjectNotFoundException::class,
        JwtParserException::class,
        DocumentDoesNotExist::class,
        NotWellFormedException::class,
        BadRequestHttpException::class,
        FormatterException::class,
        EventCannotBeAddedToProduction::class,
        EventCannotBeRemovedFromProduction::class,
    ];

    public function register(Application $app): void
    {
        self::$debug = $app['debug'] === true;

        $app[ErrorLogger::class] = $app::share(
            function (Application $app): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app, LoggerName::forWeb())
                );
            }
        );

        $app->error(
            function (Exception $e) use ($app) {
                $defaultStatus = ApiProblemJsonResponse::HTTP_BAD_REQUEST;

                $badRequest = false;
                // Don't log exceptions that are caused by user errors.
                // Use an instanceof check instead of in_array to also allow filtering on parent class or interface.
                foreach (self::BAD_REQUESTS as $badRequestExceptionClass) {
                    if ($e instanceof $badRequestExceptionClass) {
                        $badRequest = true;
                        break;
                    }
                }

                if (!$badRequest) {
                    $app[ErrorLogger::class]->log($e);
                    $defaultStatus = ApiProblemJsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                }

                $problem = $this::createNewApiProblem($e, $defaultStatus);

                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    public static function createNewApiProblem(Throwable $e, int $defaultStatus): ApiProblem
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ?: $defaultStatus);

        if ($e instanceof Error) {
            $problem->setTitle('Internal server error');
            $problem->setStatus(ApiProblemJsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($e instanceof DataValidationException) {
            $problem->setTitle('Invalid payload.');
            $problem['validation_messages'] = $e->getValidationMessages();
        }

        if ($e instanceof GroupedValidationException) {
            $problem['validation_messages'] = $e->getMessages();
        }

        if ($e instanceof CultureFeed_Exception || $e instanceof CultureFeed_HttpException) {
            $title = $problem->getTitle();

            // Remove "URL CALLED" and everything after it.
            // E.g. "event is not known in uitpas URL CALLED: https://acc.uitid.be/uitid/rest/uitpas/cultureevent/..."
            // becomes "event is not known in uitpas ".
            // The trailing space could easily be removed but it's there for backward compatibility with systems that
            // might have implemented a comparison on the error message when this was introduced in udb3-uitpas-service
            // in the past.
            $formattedTitle = preg_replace('/URL CALLED.*/', '', $title);
            $problem->setTitle($formattedTitle);
        }

        if (self::$debug) {
            $problem['debug'] = ContextExceptionConverterProcessor::convertThrowableToArray($e);
        }

        return $problem;
    }

    public function boot(Application $app): void
    {
        self::$debug = $app['debug'] === true;
    }
}
