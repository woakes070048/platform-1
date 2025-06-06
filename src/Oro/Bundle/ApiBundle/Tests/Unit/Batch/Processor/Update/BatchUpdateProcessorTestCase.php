<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;

class BatchUpdateProcessorTestCase extends TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected BatchUpdateContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new BatchUpdateContext();
        $this->context->setAction(ApiAction::UPDATE_LIST);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }
}
