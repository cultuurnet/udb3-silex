<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

class AbstractApproveTest extends AbstractModerationCommandTestBase
{
    /**
     * @inheritdoc
     */
    public function getModerationCommandClass()
    {
        return AbstractApprove::class;
    }
}
