<?php

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class UDB3SavedSearchesCommandHandler extends CommandHandler
{
    /**
     * @var SavedSearchWriteRepositoryCollection
     */
    private $savedSearchWriteRepositoryCollection;

    /**
     * @param SavedSearchWriteRepositoryCollection $savedSearchWriteRepositoryCollection
     */
    public function __construct(SavedSearchWriteRepositoryCollection $savedSearchWriteRepositoryCollection)
    {
        $this->savedSearchWriteRepositoryCollection = $savedSearchWriteRepositoryCollection;
    }

    /**
     * @param SubscribeToSavedSearch $subscribeToSavedSearch
     */
    public function handleSubscribeToSavedSearch(SubscribeToSavedSearch $subscribeToSavedSearch): void
    {
        $userId = $subscribeToSavedSearch->getUserId();
        $name = $subscribeToSavedSearch->getName();
        $query = $subscribeToSavedSearch->getQuery();

        $savedSearchRepository = $this->savedSearchWriteRepositoryCollection->getRepository(SapiVersion::V3());

        $savedSearchRepository->write($userId, $name, $query);
    }

    /**
     * @param UnsubscribeFromSavedSearch $unsubscribeFromSavedSearch
     */
    public function handleUnsubscribeFromSavedSearch(UnsubscribeFromSavedSearch $unsubscribeFromSavedSearch): void
    {
        $userId = $unsubscribeFromSavedSearch->getUserId();
        $searchId = $unsubscribeFromSavedSearch->getSearchId();

        $savedSearchRepository = $this->savedSearchWriteRepositoryCollection->getRepository(SapiVersion::V3());

        $savedSearchRepository->delete($userId, $searchId);
    }
}
