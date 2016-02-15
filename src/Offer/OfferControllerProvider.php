<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $offer_services = [
            'event' => 'event_editor_with_label_memory',
            'place' => 'place_editing_service_with_label_memory',
        ];

        foreach ($offer_services as $offerType => $serviceName) {
            $controllerName = "{$offerType}_offer_controller";

            $app[$controllerName] = $app->share(
                function (Application $app) use ($serviceName) {
                    return new EditOfferRestController(
                        $app[$serviceName],
                        new LabelJSONDeserializer()
                    );
                }
            );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/labels",
                    "{$controllerName}:addLabel"
                );

            $controllers
                ->delete(
                    "{$offerType}/{cdbid}/labels/{label}",
                    "{$controllerName}:removeLabel"
                );
        }

        return $controllers;
    }
}
