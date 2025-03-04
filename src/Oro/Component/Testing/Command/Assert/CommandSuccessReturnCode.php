<?php

namespace Oro\Component\Testing\Command\Assert;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Checks if the command return code is 0 (integer zero).
 */
class CommandSuccessReturnCode extends Constraint
{
    #[\Override]
    protected function matches($other): bool
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $other */
        return 0 === $other->getStatusCode();
    }

    #[\Override]
    protected function failureDescription($other): string
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $other */
        return sprintf(
            "Command returned success return code.\nCommand output:\n%s",
            $other->getDisplay()
        );
    }

    #[\Override]
    public function toString(): string
    {
        return 'command returned success return code';
    }
}
