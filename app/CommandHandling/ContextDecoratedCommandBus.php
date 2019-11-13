<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;
use CultuurNet\UDB3\CommandHandling\ContextAwareInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\RequestStack;

class ContextDecoratedCommandBus extends CommandBusDecoratorBase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @param CommandBusInterface $decoratee
     * @param Application $application
     */
    public function __construct(
        CommandBusInterface $decoratee,
        Application $application
    ) {
        parent::__construct($decoratee);
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($command)
    {
        if ($this->decoratee instanceof ContextAwareInterface) {
            /** @var \CultureFeed_User $user */
            $user = $this->application['current_user'];

            $contextValues = array();
            if ($user) {
                $contextValues['user_id'] = $user->id;
                $contextValues['user_nick'] = $user->nick;
                $contextValues['user_email'] = $user->mbox;
                $contextValues['auth_jwt'] = $this->application['jwt'];
                $contextValues['auth_api_key'] = $this->application['api_key'];

                /** @var TokenCredentials $tokenCredentials */
                $tokenCredentials = $this->application['culturefeed_token_credentials'];
                $contextValues['uitid_token_credentials'] = $tokenCredentials;
            }

            /** @var RequestStack $requestStack */
            $requestStack = $this->application['request_stack'];
            $request = $requestStack->getMasterRequest();
            if ($request) {
                // @todo Add to Impersonator? Sometimes an additional command
                // is dispatched by the worker itself.
                $contextValues['client_ip'] = $request->getClientIp();
            }

            $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

            $context = new \Broadway\Domain\Metadata($contextValues);

            $this->decoratee->setContext($context);
        }

        return $this->decoratee->dispatch($command);
    }
}