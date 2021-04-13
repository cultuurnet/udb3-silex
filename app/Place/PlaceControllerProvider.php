<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Http\Management\User\CultureFeedUserIdentification;
use CultuurNet\UDB3\Http\Place\EditPlaceRestController;
use CultuurNet\UDB3\Http\Place\HistoryPlaceRestController;
use CultuurNet\UDB3\Http\Place\ReadPlaceRestController;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class PlaceControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app[ReadPlaceRestController::class] = $app->share(
            function (Application $app) {
                return new ReadPlaceRestController($app['place_jsonld_repository']);
            }
        );

        $app['place_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditPlaceRestController(
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['media_manager'],
                    $app['place_iri_generator'],
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['should_auto_approve_new_offer']
                );
            }
        );

        $app['place_history_controller'] = $app->share(
            function (Application $app) {
                return new HistoryPlaceRestController(
                    $app['places_history_repository'],
                    new CultureFeedUserIdentification(
                        $app['current_user'],
                        $app['config']['user_permissions']
                    )
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'place_editing_controller:createPlace');
        $controllers->get('/{cdbid}', ReadPlaceRestController::class . ':get');
        $controllers->delete('/{cdbid}', 'place_editing_controller:deletePlace');

        $controllers->get('/{placeId}/history', 'place_history_controller:get');

        $controllers->put('/{cdbid}/address/{lang}', 'place_editing_controller:updateAddress');
        $controllers->put('/{cdbid}/bookingInfo', 'place_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contactPoint', 'place_editing_controller:updateContactPoint');
        $controllers->put('/{cdbid}/organizer/{organizerId}', 'place_editing_controller:updateOrganizer');
        $controllers->delete('/{cdbid}/organizer/{organizerId}', 'place_editing_controller:deleteOrganizer');
        $controllers->delete('/{cdbid}/typicalAgeRange', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->put('/{cdbid}/typicalAgeRange', 'place_editing_controller:updateTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'place_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main', 'place_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}', 'place_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}', 'place_editing_controller:updateImage');

        $controllers->get('/{cdbid}/calsum', ReadPlaceRestController::class . ':getCalendarSummary');
        $controllers->put('/{offerId}/status', UpdateStatusRequestHandler::class . ':handle');

        return $controllers;
    }
}
