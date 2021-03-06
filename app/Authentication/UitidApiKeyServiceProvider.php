<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class UitidApiKeyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['auth.api_key_reader'] = $app->share(
            function () {
                $queryReader = new QueryParameterApiKeyReader('apiKey');
                $headerReader = new CustomHeaderApiKeyReader('X-Api-Key');

                return new CompositeApiKeyReader(
                    $queryReader,
                    $headerReader
                );
            }
        );

        $app['auth.consumer_repository'] = $app->share(
            function (Application $app) {
                return new InMemoryConsumerRepository();
            }
        );

        $app['auth.api_key_authenticator'] = $app->share(
            function (Application $app) {
                return new CultureFeedApiKeyAuthenticator(
                    $app['culturefeed'],
                    $app['auth.consumer_repository']
                );
            }
        );

        $app['auth.request_authenticator'] = $app->share(
            function (Application $app) {
                return new ApiKeyRequestAuthenticator(
                    $app['auth.api_key_reader'],
                    $app['auth.api_key_authenticator']
                );
            }
        );

        $app['consumer'] = null;

        $app->before(
            function (Request $request, Application $app): ?Response {
                if ($app['auth.api_key_bypass']) {
                    return null;
                }

                // Don't do an API key check if an access token with Auth0 client id is used.
                if (!is_null($app['api_client_id'])) {
                    return null;
                }

                /** @var AuthorizationChecker $security */
                $security = $app['security.authorization_checker'];
                /** @var ApiKeyRequestAuthenticator $apiKeyAuthenticator */
                $apiKeyAuthenticator = $app['auth.request_authenticator'];

                $psr7Request = (new DiactorosFactory())->createRequest($request);

                // Also store the ApiKey for later use in the impersonator.
                $app['auth.api_key'] = $app['auth.api_key_reader']->read($psr7Request);

                try {
                    if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
                        // The request is not authenticated so we don't need to do additional checks since the
                        // firewall will return an unauthorized error response.
                        return null;
                    }
                } catch (AuthenticationCredentialsNotFoundException $exception) {
                    // The request is for a public URL so we can skip any checks.
                    return null;
                }

                try {
                    $apiKeyAuthenticator->authenticate($psr7Request);
                } catch (RequestAuthenticationException $e) {
                    return new ApiProblemJsonResponse(ApiProblems::unauthorized($e->getMessage()));
                }

                // Check that the API consumer linked to the API key has the required permission to use EntryAPI.
                $permissionCheck = new ConsumerIsInPermissionGroup(
                    (string) $app['auth.api_key.group_id']
                );

                /* @var ConsumerReadRepositoryInterface $consumerRepository */
                $consumerRepository = $app['auth.consumer_repository'];
                /** @var ConsumerInterface $consumer */
                $consumer = $consumerRepository->getConsumer($app['auth.api_key']);

                if (!$permissionCheck->satisfiedBy($consumer)) {
                    return new ApiProblemJsonResponse(
                        ApiProblems::forbidden('Given API key is not authorized to use EntryAPI.')
                    );
                }

                $app['consumer'] = $consumer;
                return null;
            },
            Application::LATE_EVENT
        );
    }


    public function boot(Application $app)
    {
    }
}
