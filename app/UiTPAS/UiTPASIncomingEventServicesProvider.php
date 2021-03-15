<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPAS;

use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\EventProcessManager;
use CultuurNet\UDB3\UiTPAS\Label\InMemoryUiTPASLabelsRepository;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class UiTPASIncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['uitpas_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../../log/uitpas-events.log');
            }
        );

        $app['uitpas_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('uitpas-events');
                $logger->pushHandler($app['uitpas_log_handler']);
                $logger->pushHandler(new SentryHandler($app[HubInterface::class], Logger::ERROR));
            }
        );

        $app['uitpas_deserializer_locator'] = $app->share(
            function () {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.uitpas-events.event-card-systems-updated+json'
                    ),
                    new EventCardSystemsUpdatedDeserializer()
                );
                return $deserializerLocator;
            }
        );

        $app['uitpas_event_bus_forwarding_consumer_factory'] = $app->share(
            function (Application $app) {
                return new EventBusForwardingConsumerFactory(
                    new Natural(0),
                    $app['amqp.connection'],
                    $app['uitpas_logger'],
                    $app['uitpas_deserializer_locator'],
                    $app['event_bus'],
                    new StringLiteral($app['config']['amqp']['consumer_tag'])
                );
            }
        );

        $app['amqp.uitpas_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                // If this service gets instantiated, it's because we're running the AMQP listener for UiTPAS messages
                // so we should set the API name to UiTPAS listener.
                $app['api_name'] = ApiName::UITPAS_LISTENER;

                $consumerConfig = $app['config']['amqp']['consumers']['uitpas'];
                $exchange = new StringLiteral($consumerConfig['exchange']);
                $queue = new StringLiteral($consumerConfig['queue']);

                /** @var EventBusForwardingConsumerFactory $consumerFactory */
                $consumerFactory = $app['uitpas_event_bus_forwarding_consumer_factory'];

                return $consumerFactory->create($exchange, $queue);
            }
        );

        $app[UiTPASLabelsRepository::class] = $app->share(
            function (Application $app) {
                return InMemoryUiTPASLabelsRepository::fromStrings(
                    $app['config']['uitpas']['labels']
                );
            }
        );

        $app['uitpas_event_process_manager'] = $app->share(
            function (Application $app) {
                return new EventProcessManager(
                    $app['event_command_bus'],
                    $app[UiTPASLabelsRepository::class],
                    $app['uitpas_logger']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
