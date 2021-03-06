<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Search\CountingSearchServiceInterface;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Implementation of the user permission matcher for SAPI3.
 */
class Sapi3UserPermissionMatcher implements UserPermissionMatcherInterface
{
    /**
     * @var UserConstraintsReadRepositoryInterface
     */
    private $userConstraintsReadRepository;

    /**
     * @var Sapi3SearchQueryFactory
     */
    private $searchQueryFactory;

    /**
     * @var CountingSearchServiceInterface
     */
    private $searchService;

    public function __construct(
        UserConstraintsReadRepositoryInterface $userConstraintsReadRepository,
        Sapi3SearchQueryFactory $searchQueryFactory,
        CountingSearchServiceInterface $searchService
    ) {
        $this->userConstraintsReadRepository = $userConstraintsReadRepository;
        $this->searchQueryFactory = $searchQueryFactory;
        $this->searchService = $searchService;
    }

    /**
     * @inheritdoc
     */
    public function itMatchesOffer(
        StringLiteral $userId,
        Permission $permission,
        StringLiteral $offerId
    ) {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            $userId,
            $permission
        );
        if (count($constraints) < 1) {
            return false;
        }

        $query = $this->searchQueryFactory->createFromConstraints(
            $constraints,
            $offerId
        );

        $totalItems = $this->searchService->search($query);

        return $totalItems === 1;
    }
}
