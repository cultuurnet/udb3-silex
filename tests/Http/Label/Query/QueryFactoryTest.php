<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class QueryFactoryTest extends TestCase
{
    public const QUERY_VALUE = 'label';
    public const USER_ID_VALUE = 'userId';
    public const START_VALUE = 5;
    public const LIMIT_VALUE = 10;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    protected function setUp(): void
    {
        $this->queryFactory = new QueryFactory(self::USER_ID_VALUE);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request(): void
    {
        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
            QueryFactory::START => self::START_VALUE,
            QueryFactory::LIMIT => self::LIMIT_VALUE,
        ]);

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            new StringLiteral(self::USER_ID_VALUE),
            new Natural(self::START_VALUE),
            new Natural(self::LIMIT_VALUE)
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_start(): void
    {
        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
            QueryFactory::LIMIT => self::LIMIT_VALUE,
        ]);

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            new StringLiteral(self::USER_ID_VALUE),
            null,
            new Natural(self::LIMIT_VALUE)
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_limit(): void
    {
        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
            QueryFactory::START => self::START_VALUE,
        ]);

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            new StringLiteral(self::USER_ID_VALUE),
            new Natural(self::START_VALUE),
            null
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_start_and_no_limit(): void
    {
        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
        ]);

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            new StringLiteral(self::USER_ID_VALUE),
            null,
            null
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_with_zero_start_and_zero_limit(): void
    {
        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
            QueryFactory::START => 0,
            QueryFactory::LIMIT => 0,
        ]);

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            new StringLiteral(self::USER_ID_VALUE),
            new Natural(0),
            new Natural(0)
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_return_a_query_without_user_id(): void
    {
        $queryFactory = new QueryFactory(null);

        $request = new Request([
            QueryFactory::QUERY => self::QUERY_VALUE,
            QueryFactory::START => self::START_VALUE,
            QueryFactory::LIMIT => self::LIMIT_VALUE,
        ]);

        $query = $queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            new StringLiteral(self::QUERY_VALUE),
            null,
            new Natural(self::START_VALUE),
            new Natural(self::LIMIT_VALUE)
        );

        $this->assertEquals($expectedQuery, $query);
    }
}
