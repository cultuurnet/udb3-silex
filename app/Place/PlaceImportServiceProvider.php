<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Model\Import\Place\PlaceDocumentImporter;
use CultuurNet\UDB3\Model\Import\Place\PlaceLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\PreProcessing\LabelPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Place\PlaceImportValidator;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['place_denormalizer'] = $app->share(
            function (Application $app) {
                return new PlaceDenormalizer(
                    new PlaceImportValidator(
                        new PlaceIDParser(),
                        $app['current_user_id'],
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                );
            }
        );

        $app['place_importer'] = $app->share(
            function (Application $app) {
                $placeImporter = new PlaceDocumentImporter(
                    $app['place_repository'],
                    $app['place_denormalizer'],
                    $app['import_image_collection_factory'],
                    $app['imports_command_bus'],
                    $app['should_auto_approve_new_offer'],
                    $app['labels.labels_locked_for_import_repository']
                );

                $termPreProcessor = new TermPreProcessingDocumentImporter(
                    $placeImporter,
                    new PlaceLegacyBridgeCategoryResolver()
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
