<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Approve;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Reject;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Item\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\ItemCommandHandler;
use CultuurNet\UDB3\Offer\Item\ItemRepository;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdateTitle as UpdateTitleOnSomethingElse;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdatePriceInfo as UpdatePriceInfoOnSomethingElse;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class OfferCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Title
     */
    protected $title;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var PriceInfo
     */
    protected $priceInfo;

    /**
     * @var ItemCreated
     */
    protected $itemCreated;

    /**
     * @var Repository|MockObject
     */
    protected $organizerRepository;

    /**
     * @var MediaManager|MockObject
     */
    protected $mediaManager;

    public function setUp()
    {
        parent::setUp();

        $this->id = '123';
        $this->language = new Language('en');
        $this->title = new Title('English title');
        $this->description = new StringLiteral('English description');

        $this->itemCreated = new ItemCreated(
            $this->id,
            new Language('nl')
        );

        $this->priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );
    }

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): ItemCommandHandler {
        $this->organizerRepository = $this->createMock(Repository::class);
        $this->mediaManager = $this->createMock(MediaManager::class);

        return new ItemCommandHandler(
            new ItemRepository($eventStore, $eventBus),
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    /**
     * @test
     */
    public function it_handles_translate_title_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new UpdateTitle($this->id, $this->language, $this->title)
            )
            ->then(
                [
                    new TitleTranslated($this->id, $this->language, $this->title),
                ]
            );
    }

    /**
     * @test
     */
    public function it_ignores_translate_title_commands_from_incorrect_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new UpdateTitleOnSomethingElse($this->id, $this->language, $this->title)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_price_info_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(new UpdatePriceInfo($this->id, $this->priceInfo))
            ->then(
                [
                    new PriceInfoUpdated($this->id, $this->priceInfo),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_update_price_info_if_there_were_no_changes()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new PriceInfoUpdated($this->id, $this->priceInfo),
                ]
            )
            ->when(new UpdatePriceInfo($this->id, $this->priceInfo))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_price_info_commands_from_incorrect_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(new UpdatePriceInfoOnSomethingElse($this->id, $this->priceInfo))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_approve_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Approve($this->id))
            ->then([
                new Approved($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_duplicate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsDuplicate($this->id))
            ->then([
                new FlaggedAsDuplicate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_inappropriate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsInappropriate($this->id))
            ->then([
                new FlaggedAsInappropriate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_reject_command_on_ready_for_validation_item()
    {
        $reason = new StringLiteral('reject reason');

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Reject($this->id, $reason))
            ->then([
                new Rejected($this->id, $reason),
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_facilities_of_a_place()
    {
        $facilities = [
            new Facility('facility1', 'facility label'),
        ];

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
            ])
            ->when(new UpdateFacilities($this->id, $facilities))
            ->then([
                new FacilitiesUpdated($this->id, $facilities),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_delete_current_organizer_commands()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new OrganizerUpdated($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            )
            ->when(
                new DeleteCurrentOrganizer($this->id)
            )
            ->then(
                [
                    new OrganizerDeleted($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            );
    }
}
