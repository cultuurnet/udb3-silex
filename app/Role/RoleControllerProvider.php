<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Http\Deserializer\Role\QueryJSONDeserializer;
use CultuurNet\UDB3\Http\Role\EditRoleRestController;
use CultuurNet\UDB3\Http\Role\ReadRoleRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RoleControllerProvider implements ControllerProviderInterface
{
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $app['role_controller'] = $app->share(
            function (Application $app) {
                return new ReadRoleRestController(
                    $app['role_service'],
                    $app['role_reading_service'],
                    $app['current_user_id'],
                    $app['current_user_is_god_user'],
                    $app['role_search_v3_repository'],
                    $app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY]
                );
            }
        );

        $app['role_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditRoleRestController(
                    $app['role_editing_service'],
                    $app['event_command_bus'],
                    new UpdateRoleRequestDeserializer(),
                    $app[LabelServiceProvider::READ_SERVICE],
                    new QueryJSONDeserializer()
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/roles/', 'role_controller:search');

        $controllers->post(
            '/roles/',
            'role_edit_controller:create'
        );

        $controllers
            ->get('/roles/{id}', 'role_controller:get')
            ->bind('role');

        $controllers->patch(
            '/roles/{id}',
            'role_edit_controller:update'
        );

        $controllers->post(
            '/roles/{id}/constraints/{sapiVersion}',
            'role_edit_controller:addConstraint'
        );

        $controllers->put(
            '/roles/{id}/constraints/{sapiVersion}',
            'role_edit_controller:updateConstraint'
        );

        $controllers->delete(
            '/roles/{id}/constraints/{sapiVersion}',
            'role_edit_controller:removeConstraint'
        );

        $controllers->delete('/roles/{id}', 'role_edit_controller:delete');

        $controllers
            ->get('/permissions/', 'role_controller:getPermissions');

        $controllers
            ->get('/user/permissions/', 'role_controller:getUserPermissions');

        $controllers->get(
            '/roles/{roleId}/users/',
            'role_controller:getRoleUsers'
        );

        $controllers->put(
            '/roles/{roleId}/permissions/{permissionKey}',
            'role_edit_controller:addPermission'
        );

        $controllers->delete(
            '/roles/{roleId}/permissions/{permissionKey}',
            'role_edit_controller:removePermission'
        );

        $controllers->get(
            '/roles/{roleId}/labels/',
            'role_controller:getRoleLabels'
        );

        $controllers->put(
            '/roles/{roleId}/labels/{labelIdentifier}',
            'role_edit_controller:addLabel'
        );

        $controllers->delete(
            '/roles/{roleId}/labels/{labelIdentifier}',
            'role_edit_controller:removeLabel'
        );

        $controllers->put(
            '/roles/{roleId}/users/{userId}',
            'role_edit_controller:addUser'
        );

        $controllers->delete(
            '/roles/{roleId}/users/{userId}',
            'role_edit_controller:removeUser'
        );

        $controllers->get(
            '/users/{userId}/roles/',
            'role_controller:getUserRoles'
        );

        $controllers->get(
            '/user/roles/',
            'role_controller:getCurrentUserRoles'
        );

        return $controllers;
    }
}
