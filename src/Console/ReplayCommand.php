<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use CultuurNet\UDB3\Silex\AggregateType;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReplayCommand
 *
 * @package CultuurNet\UDB3\Silex\Console
 */
class ReplayCommand extends Command
{
    const OPTION_DISABLE_PUBLISHING = 'disable-publishing';
    const OPTION_START_ID = 'start-id';
    const OPTION_DELAY = 'delay';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $aggregateTypeEnumeration = implode(', ', AggregateType::getConstants());

        $this
            ->setName('replay')
            ->setDescription('Replay the event stream to the event bus with only read models attached.')
            ->addArgument(
                'aggregate',
                InputArgument::OPTIONAL,
                'Aggregate type to replay events from. One of: ' . $aggregateTypeEnumeration . '.',
                null
            )
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_REQUIRED,
                'Alternative cache factory method to use, specify the service suffix, for example "redis".'
            )
            ->addOption(
                'subscriber',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Subscribers to register with the event bus. If not specified, all subscribers will be registered.'
            )
            ->addOption(
                self::OPTION_DISABLE_PUBLISHING,
                null,
                InputOption::VALUE_NONE,
                'Disable publishing to the event bus.'
            )
            ->addOption(
                self::OPTION_START_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The id of the row to start the replay from.'
            )
            ->addOption(
                self::OPTION_DELAY,
                null,
                InputOption::VALUE_REQUIRED,
                'Delay per message, in milliseconds.',
                0
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = (int) $input->getOption(self::OPTION_DELAY);

        $cache = $input->getOption('cache');
        if ($cache) {
            $cacheServiceName = 'cache-' . $cache;
            /** @var Application $app */
            $app = $this->getSilexApplication();

            $app['cache'] = $app->share(
                function (Application $app) use ($cacheServiceName) {
                    return $app[$cacheServiceName];
                }
            );
        }

        $subscribers = $input->getOption('subscriber');
        if (!empty($subscribers)) {
            $output->writeln(
                'Registering the following subscribers with the event bus: ' . implode(', ', $subscribers)
            );
            $this->setSubscribers($subscribers);
        }

        $aggregateType = $this->getAggregateType($input, $output);

        $startId = $input->getOption(self::OPTION_START_ID);

        $stream = $this->getEventStream($startId, $aggregateType);

        $eventBus = $this->getEventBus();

        /** @var DomainEventStream $eventStream */
        foreach ($stream() as $eventStream) {
            if ($delay > 0) {
                // Delay has to be multiplied by the number of messages in this
                // particular chunk because in theory we handle more than 1
                // message per time. In reality the stream contains 1 message.
                // Multiply by 1000 to convert to microseconds.
                usleep($delay * $eventStream->getIterator()->count() * 1000);
            }

            /** @var DomainMessage $message */
            foreach ($eventStream->getIterator() as $message) {
                $output->writeln(
                    $stream->getPreviousId() . '. ' .
                    $message->getRecordedOn()->toString() . ' ' .
                    $message->getType() .
                    ' (' . $message->getId() . ')'
                );
            }

            if (!$this->isPublishDisabled($input)) {
                $eventBus->publish($eventStream);
            }
        }
    }

    /**
     * @return EventBusInterface
     */
    private function getEventBus()
    {
        $app = $this->getSilexApplication();

        // @todo Limit the event bus to read projections.
        return $app['event_bus'];
    }

    /**
     * @param $subscribers
     */
    private function setSubscribers($subscribers)
    {
        $app = $this->getSilexApplication();

        $config = $app['config'];
        $config['event_bus']['subscribers'] = $subscribers;
        $app['config'] = $config;
    }

    /**
     * @param int $startId
     * @param AggregateType $aggregateType
     * @return EventStream
     */
    private function getEventStream($startId = null, AggregateType $aggregateType = null)
    {
        $app = $this->getSilexApplication();
        $startId = $startId !== null ? $startId : 0;

        $eventStream = new EventStream(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new SimpleInterfaceSerializer(),
            'event_store'
        );

        $eventStream = $eventStream->withStartId($startId);
        if ($aggregateType) {
            $eventStream = $eventStream->withAggregateType($aggregateType->toNative());
        }

        return $eventStream;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return AggregateType|null
     */
    private function getAggregateType(InputInterface $input, OutputInterface $output)
    {
        $aggregateTypeInput = $input->getArgument('aggregate');

        $aggregateType = null;

        if (!empty($aggregateTypeInput)) {
            $aggregateType = AggregateType::get($aggregateTypeInput);
        }

        return $aggregateType;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function isPublishDisabled(InputInterface $input)
    {
        return $input->getOption(self::OPTION_DISABLE_PUBLISHING);
    }
}
