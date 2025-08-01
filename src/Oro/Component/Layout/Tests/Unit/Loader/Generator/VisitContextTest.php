<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\TestCase;

class VisitContextTest extends TestCase
{
    public function testGetClass(): void
    {
        $class = new ClassGenerator(\uniqid('testClassName', false));
        $visitContext = new VisitContext($class);
        self::assertSame($class, $visitContext->getClass());
    }

    public function testAppendToUpdateMethodBody(): void
    {
        $visitContext = new VisitContext(new ClassGenerator());
        $visitContext->setUpdateMethodBody('echo "123";');
        $visitContext->appendToUpdateMethodBody('echo "456";');
        self::assertSame(
            <<<'CODE'
echo "123";
echo "456";
CODE
            ,
            $visitContext->getUpdateMethodBody()
        );
    }
}
