<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\Storage\DBALPurgeService;
use CultuurNet\UDB3\Storage\PurgeServiceManager;

/**
 * Class PurgeServiceProvider
 *
 * @package CultuurNet\UDB3\Silex
 */
class PurgeServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $application)
    {
        $application[PurgeServiceManager::class] = $application->share(
            function (Application $application) {
                return $this->createPurgeServiceManager($application);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }

    /**
     * @return PurgeServiceManager
     */
    private function createPurgeServiceManager(Application $application)
    {
        $purgerServiceManager = new PurgeServiceManager();
        $connection = $application['dbal_connection'];

        $this->addReadModels($purgerServiceManager, $connection);

        return $purgerServiceManager;
    }


    private function addReadModels(PurgeServiceManager $purgeServiceManager, Connection $connection)
    {
        $dbalReadModels = [
            'event_permission_readmodel',
            'event_relations',
            'labels_json',
            'label_roles',
            'labels_relations',
            'organizer_permission_readmodel',
            'place_permission_readmodel',
            'place_relations',
            'role_permissions',
            'roles_search_v3',
            'user_roles',
            'offer_metadata',
        ];

        foreach ($dbalReadModels as $dbalReadModel) {
            $purgeServiceManager->addPurgeService(
                new DBALPurgeService(
                    $connection,
                    $dbalReadModel
                )
            );
        }
    }
}
