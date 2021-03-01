<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Model\Event\EventIDParser;
use CultuurNet\UDB3\Model\Import\Event\EventDocumentImporter;
use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\PreProcessing\LabelPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Event\EventImportValidator;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['event_denormalizer'] = $app->share(
            function (Application $app) {
                return new EventDenormalizer(
                    new EventImportValidator(
                        $app['place_jsonld_repository'],
                        new EventIDParser(),
                        new CultureFeedUserIdentification(
                            $app['current_user'],
                            $app['config']['user_permissions']
                        ),
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                );
            }
        );

        $app['event_importer.file_log_handler'] = $app->share(
            function () {
                return new StreamHandler(
                    __DIR__ . '/../../log/event_importer.log'
                );
            }
        );

        $app['event_importer.logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('event_importer');
                $logger->pushProcessor(new PsrLogMessageProcessor());
                $logger->pushHandler(
                    $app['event_importer.file_log_handler']
                );

                return $logger;
            }
        );

        $app['event_importer'] = $app->share(
            function (Application $app) {
                $eventImporter = new EventDocumentImporter(
                    $app['event_repository'],
                    $app['event_denormalizer'],
                    $app['import_image_collection_factory'],
                    $app['imports_command_bus'],
                    $app['should_auto_approve_new_offer'],
                    $app['labels.labels_locked_for_import_repository'],
                    $app['event_importer.logger']
                );

                $termPreProcessor = new TermPreProcessingDocumentImporter(
                    $eventImporter,
                    new EventLegacyBridgeCategoryResolver()
                );

                $labelPreProcessor = new LabelPreProcessingDocumentImporter(
                    $termPreProcessor,
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                );

                return $labelPreProcessor;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
