<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Http\Place\ReadPlaceRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class DeprecatedPlaceControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('place/{cdbid}', ReadPlaceRestController::class . ':get')
            ->bind('place');
        $controllers->delete('place/{cdbid}', 'place_editing_controller:deletePlace');
        $controllers->get('place/{cdbid}/events', 'place_editing_controller:getEvents');

        $controllers->post('place', 'place_editing_controller:createPlace');

        $controllers->post('place/{itemId}/images', 'place_editing_controller:addImage');
        $controllers->post('place/{itemId}/images/main', 'place_editing_controller:selectMainImage');
        $controllers->post('place/{itemId}/images/{mediaObjectId}', 'place_editing_controller:updateImage');
        $controllers->delete('place/{itemId}/images/{mediaObjectId}', 'place_editing_controller:removeImage');

        $controllers->post('place/{cdbid}/address/{lang}', 'place_editing_controller:updateAddress');
        $controllers->post('place/{cdbid}/typical-age-range', 'place_editing_controller:updateTypicalAgeRange');
        $controllers->delete('place/{cdbid}/typical-age-range', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->post('place/{cdbid}/major-info', 'place_editing_controller:updateMajorInfo');
        $controllers->post('place/{cdbid}/bookingInfo', 'place_editing_controller:updateBookingInfo');
        $controllers->post('place/{cdbid}/contactPoint', 'place_editing_controller:updateContactPoint');
        $controllers->post('place/{cdbid}/organizer', 'place_editing_controller:updateOrganizerFromJsonBody');
        $controllers->delete('place/{cdbid}/organizer/{organizerId}', 'place_editing_controller:deleteOrganizer');

        return $controllers;
    }
}
