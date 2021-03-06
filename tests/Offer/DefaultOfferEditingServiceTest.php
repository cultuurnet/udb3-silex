<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTitle;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateDescription;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTheme;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateType;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class DefaultOfferEditingServiceTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var DocumentRepository|MockObject
     */
    private $offerRepository;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    private $commandFactory;

    /**
     * @var DefaultOfferEditingService
     */
    private $offerEditingService;

    /**
     * @var string
     */
    private $expectedCommandId;

    /**
     * @var AbstractUpdateTitle|MockObject
     */
    private $translateTitleCommand;

    /**
     * @var TypeResolverInterface|MockObject
     */
    private $typeResolver;

    /**
     * @var ThemeResolverInterface|MockObject
     */
    private $themeResolver;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->offerRepository = $this->createMock(DocumentRepository::class);
        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);
        $this->typeResolver = $this->createMock(TypeResolverInterface::class);
        $this->themeResolver = $this->createMock(ThemeResolverInterface::class);

        $this->translateTitleCommand = $this->getMockForAbstractClass(
            AbstractUpdateTitle::class,
            ['foo', new Language('en'), new Title('English title')]
        );

        $this->offerEditingService = new DefaultOfferEditingService(
            $this->commandBus,
            $this->uuidGenerator,
            $this->offerRepository,
            $this->commandFactory,
            $this->typeResolver,
            $this->themeResolver
        );

        $this->expectedCommandId = '123456';
    }

    /**
     * @test
     */
    public function it_can_update_a_title_in_a_given_language()
    {
        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(new JsonDocument('foo'));

        $this->commandFactory->expects($this->once())
            ->method('createUpdateTitleCommand')
            ->with('foo', new Language('en'), new Title('English title'))
            ->willReturn(new UpdateTitle('foo', new Language('en'), new Title('English title')));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->expectedCommandId);

        $commandId = $this->offerEditingService->updateTitle(
            'foo',
            new Language('en'),
            new Title('English title')
        );

        $this->assertEquals($this->expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_can_update_the_description_in_a_given_language()
    {
        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(new JsonDocument('foo'));

        $this->commandFactory->expects($this->once())
            ->method('createUpdateDescriptionCommand')
            ->with('foo', new Language('fr'), new Description('La description'))
            ->willReturn(new UpdateDescription('foo', new Language('fr'), new Description('La description')));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->expectedCommandId);

        $commandId = $this->offerEditingService->updateDescription(
            'foo',
            new Language('fr'),
            new Description('La description')
        );

        $this->assertEquals($this->expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_can_update_price_info()
    {
        $aggregateId = '940ce4d1-740b-43d2-a1a6-85be04a3eb30';
        $expectedCommandId = 'f42802e4-c1f1-4aa6-9909-a08cfc66f355';

        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $updatePriceInfoCommand = $this->getMockForAbstractClass(
            AbstractUpdatePriceInfo::class,
            [$aggregateId, $priceInfo]
        );

        $this->commandFactory->expects($this->once())
            ->method('createUpdatePriceInfoCommand')
            ->with($aggregateId, $priceInfo)
            ->willReturn($updatePriceInfoCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updatePriceInfoCommand)
            ->willReturn($expectedCommandId);

        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with('940ce4d1-740b-43d2-a1a6-85be04a3eb30')
            ->willReturn(new JsonDocument('940ce4d1-740b-43d2-a1a6-85be04a3eb30'));

        $commandId = $this->offerEditingService->updatePriceInfo(
            $aggregateId,
            $priceInfo
        );

        $this->assertEquals($expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_should_guard_that_a_document_exists_for_a_given_id()
    {
        $unknownId = '8FEFDA81-993D-4F33-851F-C19F8CB90712';

        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with($unknownId)
            ->willThrowException(DocumentDoesNotExist::withId($unknownId));

        $this->expectException(EntityNotFoundException::class);

        $this->offerEditingService->guardId($unknownId);
    }

    /**
     * @param string $offerId
     */
    private function expectPlaceholderDocument($offerId)
    {
        $this->offerRepository->expects($this->once())
            ->method('fetch')
            ->with($offerId)
            ->willReturn(new JsonDocument($offerId));
    }

    /**
     * @test
     */
    public function it_should_update_an_offer_type_and_return_the_resulting_command()
    {
        $expectedCommandId = 'f42802e4-c1f1-4aa6-9909-a08cfc66f355';
        $offerId = '2D015370-7CBA-4CB9-B0E4-07D2DEAAB2FF';
        $type = new EventType('0.15.0.0.0', 'Natuur, park of tuin');
        $this->expectPlaceholderDocument($offerId);

        $this->commandFactory->expects($this->once())
            ->method('createUpdateTypeCommand')
            ->with($offerId, $type)
            ->willReturn(new UpdateType($offerId, $type));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new UpdateType($offerId, $type))
            ->willReturn($expectedCommandId);

        $this->typeResolver
            ->expects($this->once())
            ->method('byId')
            ->with('0.15.0.0.0')
            ->willReturn($type);

        $commandId = $this->offerEditingService->updateType(
            '2D015370-7CBA-4CB9-B0E4-07D2DEAAB2FF',
            new StringLiteral('0.15.0.0.0')
        );

        $this->assertEquals($expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_should_update_an_offer_theme_and_return_the_resulting_command()
    {
        $expectedCommandId = 'f42802e4-c1f1-4aa6-9909-a08cfc66f355';
        $offerId = '2D015370-7CBA-4CB9-B0E4-07D2DEAAB2FF';
        $theme = new Theme('0.52.0.0.0', 'Circus');
        $updateThemeCommand = new UpdateTheme($offerId, $theme);
        $this->expectPlaceholderDocument($offerId);

        $this->commandFactory->expects($this->once())
            ->method('createUpdateThemeCommand')
            ->with($offerId, $theme)
            ->willReturn($updateThemeCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateThemeCommand)
            ->willReturn($expectedCommandId);

        $this->themeResolver
            ->expects($this->once())
            ->method('byId')
            ->with('0.52.0.0.0')
            ->willReturn($theme);

        $commandId = $this->offerEditingService->updateTheme(
            '2D015370-7CBA-4CB9-B0E4-07D2DEAAB2FF',
            new StringLiteral('0.52.0.0.0')
        );

        $this->assertEquals($expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_can_update_facilities_and_return_resulting_command()
    {
        $expectedCommandId = 'f42802e4-c1f1-4aa6-9909-a08cfc66f355';
        $offerId = '2D015370-7CBA-4CB9-B0E4-07D2DEAAB2FF';
        $facilities = [
            'facility1',
            'facility2',
        ];
        $updateFacilities = new UpdateFacilities($offerId, $facilities);
        $this->expectPlaceholderDocument($offerId);

        $this->commandFactory->expects($this->once())
            ->method('createUpdateFacilitiesCommand')
            ->with($offerId, $facilities)
            ->willReturn($updateFacilities);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateFacilities)
            ->willReturn($expectedCommandId);

        $commandId = $this->offerEditingService->updateFacilities($offerId, $facilities);

        $this->assertEquals($expectedCommandId, $commandId);
    }
}
