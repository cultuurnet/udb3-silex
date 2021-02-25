<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Carbon\Carbon;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConcludeCommand extends AbstractConcludeCommand
{
    const TIMEZONE = 'Europe/Brussels';
    const LUCENE_DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @var SearchServiceInterface
     */
    private $searchService;

    public function __construct(CommandBus $commandBus, SearchServiceInterface $searchService)
    {
        parent::__construct($commandBus);
        $this->searchService = $searchService;
    }


    public function configure()
    {
        $this
            ->setName('event:conclude')
            ->setDescription('Conclude events of which the end date falls in a specific date range.')
            ->addArgument(
                'lower-boundary',
                InputArgument::OPTIONAL,
                'The lower boundary of the end date range',
                Carbon::yesterday(self::TIMEZONE)->toDateTimeString()
            )
            ->addArgument(
                'upper-boundary',
                InputArgument::OPTIONAL,
                'The upper boundary of the end date range',
                Carbon::yesterday(self::TIMEZONE)->setTime(23, 59, 59)->toDateTimeString()
            )
            ->addOption(
                'page-size',
                null,
                InputOption::VALUE_REQUIRED,
                'How many items should be retrieved per page',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        [$lowerDateBoundary, $upperDateBoundary] = $this->processArguments($input);
        $query = $this->createLuceneQuery($lowerDateBoundary, $upperDateBoundary);

        $output->writeln('Executing search query: ' . $query);

        $finder = $this->createFinder(intval($input->getOption('page-size')));

        /** @var IriOfferIdentifier[] $results */
        $results = $finder->search($query);

        foreach ($results as $result) {
            $output->writeln((string) $result->getIri());

            $this->dispatchConclude($result->getId());
        }

        return 0;
    }

    private function createLuceneQuery(Carbon $lowerDateBoundary = null, Carbon $upperDateBoundary = null)
    {
        $sapiDateRange = $this->createLuceneDateRangeString($lowerDateBoundary, $upperDateBoundary);

        return "_type:event AND availableRange:{$sapiDateRange}";
    }

    /**
     * @param Carbon|null $lowerDateBoundary
     * @param Carbon|null $upperDateBoundary
     *
     * @return string
     */
    private function createLuceneDateRangeString(Carbon $lowerDateBoundary = null, Carbon $upperDateBoundary = null)
    {
        $from = $lowerDateBoundary ? $this->getLuceneFormattedDateTime($lowerDateBoundary) : '*';
        $to = $this->getLuceneFormattedDateTime($upperDateBoundary);

        return "[{$from} TO {$to}]";
    }

    /**
     * @param Carbon $date
     * @return string
     */
    private function getLuceneFormattedDateTime(Carbon $date)
    {
        return $date->tz('UTC')->format(self::LUCENE_DATE_TIME_FORMAT);
    }

    private function createFinder(int $pageSize) : ResultsGenerator
    {
        return new ResultsGenerator(
            $this->searchService,
            null,
            $pageSize
        );
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function processArguments(InputInterface $input)
    {
        $lowerBoundaryInput = $input->getArgument('lower-boundary');

        $lowerDateBoundary = null;
        if ($lowerBoundaryInput !== '*') {
            $lowerDateBoundary = new Carbon(
                $lowerBoundaryInput,
                self::TIMEZONE
            );
        }

        $upperDateBoundary = new Carbon(
            $input->getArgument('upper-boundary'),
            self::TIMEZONE
        );

        if ($lowerDateBoundary && $lowerDateBoundary->greaterThanOrEqualTo($upperDateBoundary)) {
            throw new \InvalidArgumentException(
                'lower-boundary needs to be before upper-boundary'
            );
        }

        $latestAllowedUpperBoundary = Carbon::yesterday(self::TIMEZONE)
            ->setTime(23, 59, 59);
        if ($upperDateBoundary->greaterThan($latestAllowedUpperBoundary)) {
            throw new \InvalidArgumentException(
                'your upper boundary is too high, latest allowed upper boundary is ' . $latestAllowedUpperBoundary->toDateTimeString()
            );
        }

        return array($lowerDateBoundary, $upperDateBoundary);
    }
}
