<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Place\GeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Place\GeoCoordinatesProcessManager;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['place_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                $handler = new GeoCoordinatesCommandHandler(
                    $app['place_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app[GeocodingService::class]
                );

                $handler->setLogger(LoggerFactory::create($app, LoggerName::forService('geo-coordinates', 'place')));

                return $handler;
            }
        );

        $app['place_geocoordinates_process_manager'] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $app['event_command_bus'],
                        new CultureFeedAddressFactory(),
                        LoggerFactory::create($app, LoggerName::forService('geo-coordinates', 'place'))
                    )
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
