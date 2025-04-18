<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Transforms the request data via the form from the context.
 */
class SubmitForm implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if (!$context->hasForm()) {
            // no form
            return;
        }

        $form = $context->getForm();
        if ($form->isSubmitted()) {
            // the form is already submitted
            return;
        }

        $requestData = $context->getRequestData();
        /**
         * as Symfony Form treats false as NULL due to checkboxes {@see \Symfony\Component\Form\Form::submit}
         * we have to convert false to its string representation here
         */
        array_walk_recursive($requestData, function (&$value) {
            if (false === $value) {
                $value = 'false';
            }
        });

        /**
         * always use $clearMissing = false, more details in:
         * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler::validate
         * @see \Oro\Bundle\ApiBundle\Processor\Shared\BuildFormBuilder::$enableFullValidation
         */
        $form->submit($requestData, false);
    }
}
