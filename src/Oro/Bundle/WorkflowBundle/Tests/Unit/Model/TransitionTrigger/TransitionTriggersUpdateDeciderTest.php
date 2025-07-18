<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdateDecider;
use PHPUnit\Framework\TestCase;

class TransitionTriggersUpdateDeciderTest extends TestCase
{
    /**
     * @dataProvider decideData
     */
    public function testDecide(array $expected, array $existing, array $new): void
    {
        $decider = new TransitionTriggersUpdateDecider();

        [$expectedToAdd, $expectedToRemove] = $expected;

        [$retrievedToAdd, $retrievedToRemove] = $decider->decide($existing, $new);

        $this->assertSame(
            $expectedToAdd,
            $retrievedToAdd,
            'same as new instances must be kept to be stored'
        );
        $this->assertSame(
            $expectedToRemove,
            $retrievedToRemove,
            'same as stored instances must be kept for removal'
        );
    }

    public function decideData(): array
    {
        $cron1type1 = (new TransitionCronTrigger())->setCron('* * * * *');
        $cron2type2 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $cron3type2 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $event2type2 = (new TransitionEventTrigger())->setEvent('update');

        return [
            'equal must be kept' => [
                [[], []],
                [$cron2type2],
                [$cron3type2]
            ],
            'different' => [
                //stored cron3type2 is equal to new cron2type2 so will be kept and cron2type2 will not be added
                [[$event2type2], [$cron1type1]],
                [$cron1type1, $cron3type2],
                [$event2type2, $cron2type2]
            ]
        ];
    }
}
