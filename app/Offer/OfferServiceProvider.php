<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Http\CompositePsr7RequestAuthorizer;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\DefaultExternalOfferEditingService;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\LocalOfferReadingService;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Popularity\DBALPopularityRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app[PopularityRepository::class] = $app->share(
            function (Application $app) {
                return new DBALPopularityRepository(
                    $app['dbal_connection']
                );
            }
        );

        $app['offer_reading_service'] = $app->share(
            function (Application $app) {
                return (new LocalOfferReadingService($app['iri_offer_identifier_factory']))
                    ->withDocumentRepository(OfferType::EVENT(), $app['event_jsonld_repository'])
                    ->withDocumentRepository(OfferType::PLACE(), $app['place_jsonld_repository']);
            }
        );

        $app['external_offer_editing_service'] = $app->share(
            function (Application $app) {
                return new DefaultExternalOfferEditingService(
                    $app['http.guzzle'],
                    $app['http.guzzle_psr7_factory'],
                    new CompositePsr7RequestAuthorizer(
                        $app['http.jwt_request_authorizer'],
                        $app['http.api_key_request_authorizer']
                    )
                );
            }
        );

        $app['iri_offer_identifier_factory'] = $app->share(
            function (Application $app) {
                return new IriOfferIdentifierFactory(
                    $app['config']['offer_url_regex']
                );
            }
        );

        $app['should_auto_approve_new_offer'] = $app->share(
            function (Application $app) {
                return new ConsumerIsInPermissionGroup(
                    new StringLiteral((string) $app['config']['uitid']['auto_approve_group_id'])
                );
            }
        );

        $app[OfferRepository::class] = $app->share(
            function (Application $app) {
                return new OfferRepository(
                    $app['event_repository'],
                    $app['place_repository']
                );
            }
        );

        $app[UpdateStatusHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateStatusHandler($app[OfferRepository::class]);
            }
        );

        $app[ChangeOwnerHandler::class] = $app->share(
            function (Application $app) {
                return new ChangeOwnerHandler(
                    $app[OfferRepository::class],
                    $app['offer_permission_query']
                );
            }
        );

        $app[AddLabelHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelHandler(
                    $app[OfferRepository::class],
                    $app['labels.constraint_aware_service'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[RemoveLabelHandler::class] = $app->share(
            function (Application $app) {
                return new RemoveLabelHandler($app[OfferRepository::class]);
            }
        );

        $app[ImportLabelsHandler::class] = $app->share(
            function (Application $app) {
                return new ImportLabelsHandler(
                    $app[OfferRepository::class],
                    $app['labels.constraint_aware_service']
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
