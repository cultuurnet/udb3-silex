#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\Event\LocationMarkedAsDuplicateProcessManager;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Console\ConcludeByCdbidCommand;
use CultuurNet\UDB3\Silex\Console\ConcludeCommand;
use CultuurNet\UDB3\Silex\Console\DispatchMarkedAsDuplicateEventCommand;
use CultuurNet\UDB3\Silex\Console\EventAncestorsCommand;
use CultuurNet\UDB3\Silex\Console\EventCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Silex\Console\GeocodeEventCommand;
use CultuurNet\UDB3\Silex\Console\GeocodePlaceCommand;
use CultuurNet\UDB3\Silex\Console\ImportEventCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\ImportPlaceCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\ImportRoleConstraintsCommand;
use CultuurNet\UDB3\Silex\Console\ImportSavedSearchesCommand;
use CultuurNet\UDB3\Silex\Console\MarkPlaceAsDuplicateCommand;
use CultuurNet\UDB3\Silex\Console\PermissionCommand;
use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use CultuurNet\UDB3\Silex\Console\ReplayCommand;
use CultuurNet\UDB3\Silex\Console\SearchCacheClearCommand;
use CultuurNet\UDB3\Silex\Console\SearchCacheWarmCommand;
use CultuurNet\UDB3\Silex\Console\UpdateCdbXMLCommand;
use CultuurNet\UDB3\Silex\Console\ValidatePlaceJsonLdCommand;
use Knp\Provider\ConsoleServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(
    new ConsoleServiceProvider(),
    [
        'console.name' => 'UDB3',
        'console.version' => '0.0.1',
        'console.project_directory' => __DIR__ . '/..',
    ]
);

/** @var \Knp\Console\Application $consoleApp */
$consoleApp = $app['console'];

// An udb3 system user is needed for conclude and geocode commands.
// Because of the changes for geocoding the amqp forwarding for udb2 imports also needs a user.
// To avoid fixing this locally in the amqp-silex lib, all CLI commands are executed as udb3 system user.
$app['impersonator']->impersonate(
    new Metadata(
        [
            'user_id' => SYSTEM_USER_UUID,
            'user_nick' => 'udb3',
        ]
    )
);

$app['api_name'] = ApiName::CLI;

$consoleApp->add(
    (new ConsumeCommand('amqp-listen', 'amqp.udb2_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-uitpas', 'amqp.uitpas_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-imports', 'import_command_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-curators', 'curators_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(new ReplayCommand($app['event_command_bus']));
$consoleApp->add(new EventAncestorsCommand($app['event_command_bus'], $app['event_store']));
$consoleApp->add(new UpdateCdbXMLCommand());
$consoleApp->add(new EventCdbXmlCommand());
$consoleApp->add(new PurgeModelCommand());
$consoleApp->add(new ConcludeCommand($app['event_command_bus'], $app['sapi3_search_service']));
$consoleApp->add(new ConcludeByCdbidCommand($app['event_command_bus']));
$consoleApp->add(new GeocodePlaceCommand($app['event_command_bus'], $app['dbal_connection']));
$consoleApp->add(new GeocodeEventCommand($app['event_command_bus'], $app['dbal_connection'], $app['event_jsonld_repository']));
$consoleApp->add(new PermissionCommand());
$consoleApp->add(new FireProjectedToJSONLDForRelationsCommand($app['event_bus'], $app['dbal_connection']));
$consoleApp->add(new FireProjectedToJSONLDCommand($app['event_bus']));
$consoleApp->add(new ImportSavedSearchesCommand($app['event_command_bus']));
$consoleApp->add(new ImportRoleConstraintsCommand($app['event_command_bus']));
$consoleApp->add(new ImportEventCdbXmlCommand($app['event_command_bus'], $app['event_bus']));
$consoleApp->add(new ImportPlaceCdbXmlCommand($app['event_command_bus']));
$consoleApp->add(new ValidatePlaceJsonLdCommand($app['event_command_bus']));
$consoleApp->add(new MarkPlaceAsDuplicateCommand($app['event_command_bus'], $app[LocationMarkedAsDuplicateProcessManager::class]));
$consoleApp->add(new DispatchMarkedAsDuplicateEventCommand($app['event_command_bus'], $app[LocationMarkedAsDuplicateProcessManager::class], $app['event_bus']));

$consoleApp->run();
