<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventHistoryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[HistoryProjector::class] = $app->share(
            function ($app) {
                $projector = new HistoryProjector(
                    $app['event_history_repository']
                );

                return $projector;
            }
        );

        $app['event_history_repository'] = $app->share(
            function ($app) {
                return new CacheDocumentRepository(
                    $app['cache']('event_history')
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
