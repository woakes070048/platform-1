<?php

namespace Oro\Component\Action\Model;

use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Processes extendable condition errors.
 */
interface ExtendableConditionEventErrorsProcessorInterface
{
    public function getPreparedErrors(
        ExtendableConditionEvent $event,
        array|\ArrayAccess|null &$errorsCollection = null
    ): array;

    public function showErrors(iterable $errors, string $messageType): void;

    public function processErrors(
        ExtendableConditionEvent $event,
        bool $showErrors = false,
        array|\ArrayAccess|null &$errorsCollection = null,
        string $messageType = 'error'
    ): array;
}
