<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use Resque_Job_Status;
use ValueObjects\StringLiteral\StringLiteral;

class ResqueJobStatusFactory implements JobsStatusFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createFromJobId(StringLiteral $jobId)
    {
        $resqueJobStatus = new Resque_Job_Status($jobId->toNative());
        $code = $resqueJobStatus->get();

        if ($code) {
            return JobStatus::getByOrdinal($code - 1);
        } else {
            return null;
        }
    }
}
