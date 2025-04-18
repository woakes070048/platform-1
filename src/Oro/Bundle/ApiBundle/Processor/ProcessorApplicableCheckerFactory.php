<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;

/**
 * Creates an applicable checker that should be used to check whether API processor should be executed or not.
 */
class ProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    #[\Override]
    public function createApplicableChecker(): ChainApplicableChecker
    {
        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new MatchApplicableChecker([], ['class', 'parentClass']));

        return $applicableChecker;
    }
}
