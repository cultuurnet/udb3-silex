<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Offer\DefaultExternalOfferEditingService;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\LocalOfferReadingService;
use CultuurNet\UDB3\Offer\OfferType;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OfferServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
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
                    $app['http.jwt_request_authorizer']
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
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
