<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Symfony\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use CultuurNet\UDB3\Symfony\Offer\OfferPermissionController;
use CultuurNet\UDB3\Symfony\Offer\OfferPermissionsController;
use CultuurNet\UDB3\Symfony\Offer\PatchOfferRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $offerServices = [
            'event' => 'event_editor',
            'events' => 'event_editor',
            'place' => 'place_editing_service',
            'places' => 'place_editing_service',
        ];

        foreach ($offerServices as $offerType => $serviceName) {
            $controllerName = "{$offerType}_offer_controller";
            $patchControllerName = "patch_{$offerType}_controller";
            $permissionsControllerName = "permissions_{$offerType}_controller";
            /** @deprecated */
            $permissionControllerName = "permission_{$offerType}_controller";

            $app[$controllerName] = $app->share(
                function (Application $app) use ($serviceName) {
                    return new EditOfferRestController(
                        $app[$serviceName],
                        new LabelJSONDeserializer(),
                        new TitleJSONDeserializer(false, new StringLiteral('name')),
                        new DescriptionJSONDeserializer(),
                        new PriceInfoJSONDeserializer()
                    );
                }
            );

            $app[$patchControllerName] = $app->share(
                function (Application $app) use ($offerType) {
                    return new PatchOfferRestController(
                        OfferType::fromCaseInsensitiveValue($offerType),
                        $app['event_command_bus']
                    );
                }
            );

            $app[$permissionsControllerName] = $app->share(
                function (Application $app) use ($offerType) {
                    $currentUserId = null;
                    if (!is_null($app['current_user'])) {
                        $currentUserId = new StringLiteral($app['current_user']->id);
                    }
                    $permissionsToCheck = array(
                        Permission::AANBOD_BEWERKEN(),
                        Permission::AANBOD_MODEREREN(),
                        Permission::AANBOD_VERWIJDEREN(),
                    );
                    return new OfferPermissionsController(
                        $permissionsToCheck,
                        $app['offer_permission_voter'],
                        $currentUserId
                    );
                }
            );

            /** @deprecated */
            $app[$permissionControllerName] = $app->share(
                function (Application $app) use ($offerType) {
                    $currentUserId = null;
                    if (!is_null($app['current_user'])) {
                        $currentUserId = new StringLiteral($app['current_user']->id);
                    }

                    return new OfferPermissionController(
                        Permission::AANBOD_BEWERKEN(),
                        $app['offer_permission_voter'],
                        $currentUserId
                    );
                }
            );

            $controllers->delete("{$offerType}/{cdbid}/labels/{label}", "{$controllerName}:removeLabel");

            $controllers->put("{$offerType}/{cdbid}/labels/{label}", "{$controllerName}:addLabel");

            $controllers->put("{$offerType}/{cdbid}/name/{lang}", "{$controllerName}:updateTitle");
            $controllers->put("{$offerType}/{cdbid}/description/{lang}", "{$controllerName}:updateDescription");
            $controllers->put("{$offerType}/{cdbid}/priceInfo", "{$controllerName}:updatePriceInfo");
            $controllers->patch("{$offerType}/{cdbid}", "{$patchControllerName}:handle");
            $controllers->get("{$offerType}/{offerId}/permissions/", "{$permissionsControllerName}:getPermissionsForCurrentUser");
            $controllers->get("{$offerType}/{offerId}/permissions/{userId}", "{$permissionsControllerName}:getPermissionsForGivenUser");


            /* @deprecated */
            $controllers
                ->post(
                    "{$offerType}/{cdbid}/labels",
                    "{$controllerName}:addLabelFromJsonBody"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/title",
                    "{$controllerName}:updateTitle"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/description",
                    "{$controllerName}:updateDescription"
                );

            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission",
                    "{$permissionControllerName}:currentUserHasPermission"
                );

            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission/{userId}",
                    "{$permissionControllerName}:givenUserHasPermission"
                );
        }

        return $controllers;
    }
}
