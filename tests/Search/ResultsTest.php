<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueObjects\Number\Integer;
use ValueObjects\Web\Url;

class ResultsTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_instantiated_with_result_items_and_total()
    {
        $items = OfferIdentifierCollection::fromArray(
            [
                new IriOfferIdentifier(
                    Url::fromNative('http://du.de/event/1'),
                    '1',
                    OfferType::EVENT()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://du.de/event/2'),
                    '2',
                    OfferType::EVENT()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://du.de/event/3'),
                    '3',
                    OfferType::EVENT()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://du.de/event/4'),
                    '4',
                    OfferType::EVENT()
                ),
            ]
        );
        $totalItems = new Integer(20);

        $results = new Results($items, $totalItems);

        $this->assertEquals($items->toArray(), $results->getItems());
        $this->assertEquals($totalItems, $results->getTotalItems());
    }

    /**
     * @test
     */
    public function it_only_accepts_an_items_array()
    {
        $this->expectException(TypeError::class);
        new Results('foo', new Integer(5));
    }

    /**
     * @test
     */
    public function it_only_accepts_a_total_items_integer()
    {
        $this->expectException(TypeError::class);

        new Results(
            OfferIdentifierCollection::fromArray(
                [
                    new IriOfferIdentifier(Url::fromNative('http://du.de/event/1'), '1', OfferType::EVENT()),
                ]
            ),
            'foo'
        );
    }
}
