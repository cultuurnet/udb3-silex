<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Event\GeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Event\GeoCoordinatesProcessManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['event_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                return new GeoCoordinatesCommandHandler(
                    $app['event_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app['geocoding_service']
                );
            }
        );

        $app['event_geocoordinates_process_manager'] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $app['event_command_bus'],
                        new CultureFeedAddressFactory()
                    )
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
