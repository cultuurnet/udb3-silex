<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Organizer;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label\DocumentLabelPermissionRule;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Organizer\WebsiteLookupServiceInterface;

class OrganizerImportValidator extends OrganizerValidator
{
    /**
     * @param bool $urlRequired
     */
    public function __construct(
        WebsiteLookupServiceInterface $websiteLookupService,
        UUIDParser $uuidParser,
        string $userId,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelRelationsRepository,
        $urlRequired = false
    ) {
        $extraRules = [
            new OrganizerHasUniqueUrlValidator(
                new OrganizerIDParser(),
                $websiteLookupService
            ),
            new DocumentLabelPermissionRule(
                $uuidParser,
                $userId,
                $labelsRepository,
                $labelRelationsRepository
            ),
        ];

        parent::__construct($extraRules, $urlRequired);
    }
}
