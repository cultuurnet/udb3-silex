<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Event;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Validation\Event\EventValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventImportValidatorTest extends TestCase
{
    /**
     * @var DocumentRepository|MockObject
     */
    private $placeRepository;

    /**
     * @var UUIDParser|MockObject
     */
    private $uuidParser;

    /**
     * @var LabelsRepository|MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|MockObject
     */
    private $labelRelationsRepository;

    protected function setUp()
    {
        $this->placeRepository = $this->createMock(DocumentRepository::class);

        $this->uuidParser = $this->createMock(UUIDParser::class);

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);
    }

    /**
     * @test
     */
    public function it_creates_place_validator_for_document()
    {
        $eventDocumentValidator = new EventImportValidator(
            $this->placeRepository,
            $this->uuidParser,
            'user_id',
            $this->labelsRepository,
            $this->labelRelationsRepository
        );

        $this->assertInstanceOf(EventValidator::class, $eventDocumentValidator);
    }
}
