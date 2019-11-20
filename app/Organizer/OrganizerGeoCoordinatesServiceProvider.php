<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateGeoCoordinatesFromAddressCommandHandler;
use CultuurNet\UDB3\Organizer\ProcessManager\GeoCoordinatesProcessManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['organizer_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                return new UpdateGeoCoordinatesFromAddressCommandHandler(
                    $app['organizer_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app['geocoding_service']
                );
            }
        );

        $app['organizer_geocoordinates_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../log/organizer_geocoordinates.log');
            }
        );

        $app['organizer_geocoordinates_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('organizer-geocoordinates');
                $logger->pushHandler($app['organizer_geocoordinates_log_handler']);
                return $logger;
            }
        );

        /** @var \Qandidate\Toggle\ToggleManager $toggles */
        $toggles = $app['toggles'];

        $replayFiltering = $toggles->active(
            'organizer-geocordinates-replay-filtering-event',
            $app['toggles.context']
        );

        $app['organizer_geocoordinates_process_manager'] = $app->share(
            function (Application $app) use ($replayFiltering) {
                $processManager = new GeoCoordinatesProcessManager(
                    $app['event_command_bus'],
                    new CultureFeedAddressFactory(),
                    $app['organizer_geocoordinates_logger']
                );

                if (!$replayFiltering) {
                    return $processManager;
                }

                return new ReplayFilteringEventListener($processManager);
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
