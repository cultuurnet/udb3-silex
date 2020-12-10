<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use CultuurNet\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReindexOffersWithPopularityScore extends Command
{
    private $allowedTypes = [
        'event',
        'place',
    ];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ReplayModeEventBusInterface
     */
    private $eventBus;

    /**
     * @var DocumentEventFactory
     */
    private $eventFactoryForEvents;

    /**
     * @var DocumentEventFactory
     */
    private $eventFactoryForPlaces;

    public function __construct(
        Connection $connection,
        ReplayModeEventBusInterface $eventBus,
        DocumentEventFactory $eventFactoryForEvents,
        DocumentEventFactory  $eventFactoryForPlaces
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->eventBus = $eventBus;
        $this->eventFactoryForEvents = $eventFactoryForEvents;
        $this->eventFactoryForPlaces = $eventFactoryForPlaces;
    }

    protected function configure(): void
    {
        $this
            ->setName('offer:reindex-offers-with-popularity')
            ->setDescription('Reindex events or places that have a popularity score.')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The type of the offer, either place or event.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        if (!\in_array($type, $this->allowedTypes, true)) {
            throw new \InvalidArgumentException('The type "' . $type . '" is not supported. Use event or place.');
        }

        $offerIds = $this->getOfferIds($type);
        if (count($offerIds) < 1) {
            $output->writeln('No ' . $type . 's found with a popularity.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $type, count($offerIds))) {
            return 0;
        }

        $this->eventBus->startReplayMode();

        foreach ($offerIds as $offerId) {
            $this->dispatchEvent($type, $offerId);
        }

        $this->eventBus->stopReplayMode();

        return 0;
    }

    private function getOfferIds(string $type): array
    {
        return $this->connection->createQueryBuilder()
            ->select('offer_id')
            ->from('offer_popularity')
            ->where('offer_type = :type')
            ->setParameter(':type', $type)
            ->execute()
            ->fetchAll(FetchMode::COLUMN);
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, string $type, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Reindex ' . $count . ' ' . $type . 's? [y/N] ', false)
            );
    }

    private function dispatchEvent(string $type, string $id): void
    {
        if ($type === 'place') {
            $event = $this->eventFactoryForPlaces->createEvent($id);
        } else {
            $event = $this->eventFactoryForEvents->createEvent($id);
        }

        $this->eventBus->publish(new DomainEventStream([$event]));
    }
}
