<?php

namespace CultuurNet\UDB3\Silex\Export;

use CultuurNet\UDB3\EventExport\Command\ExportEventsAsCSVJSONDeserializer;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLDJSONDeserializer;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXMLJSONDeserializer;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDFJSONDeserializer;
use CultuurNet\UDB3\Symfony\CommandDeserializerController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExportControllerProvider implements ControllerProviderInterface
{
    /**
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['json_export_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new ExportEventsAsJsonLDJSONDeserializer(),
                    $app['event_command_bus']
                );
            }
        );

        $app['csv_export_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new ExportEventsAsCSVJSONDeserializer(),
                    $app['event_command_bus']
                );
            }
        );

        $app['ooxml_export_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new ExportEventsAsOOXMLJSONDeserializer(),
                    $app['event_command_bus']
                );
            }
        );

        $app['pdf_export_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new ExportEventsAsPDFJSONDeserializer(),
                    $app['event_command_bus']
                );
            }
        );

        $controllers = $app['controllers_factory'];

        $controllers->post('/json', 'json_export_controller:handle');
        $controllers->post('/csv', 'csv_export_controller:handle');
        $controllers->post('/ooxml', 'ooxml_export_controller:handle');
        $controllers->post('/pdf', 'pdf_export_controller:handle');

        return $controllers;
    }
}
