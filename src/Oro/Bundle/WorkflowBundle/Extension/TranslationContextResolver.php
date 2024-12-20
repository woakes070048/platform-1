<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The translation context resolver for workflows translation keys.
 */
class TranslationContextResolver implements TranslationContextResolverInterface
{
    const TRANSLATION_TEMPLATE = 'oro.workflow.translation.context.{{ template }}';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var KeyTemplateParametersResolver */
    protected $resolver;

    /** @var TranslationKeyTemplateInterface[] */
    protected $templates;

    public function __construct(TranslatorInterface $translator, KeyTemplateParametersResolver $resolver)
    {
        $this->translator = $translator;
        $this->resolver = $resolver;

        $this->templates = [
            new KeyTemplate\WorkflowLabelTemplate(),
            new KeyTemplate\TransitionLabelTemplate(),
            new KeyTemplate\TransitionWarningMessageTemplate(),
            new KeyTemplate\StepLabelTemplate(),
            new KeyTemplate\TransitionAttributeLabelTemplate(),
            new KeyTemplate\WorkflowAttributeLabelTemplate(),
        ];
    }

    #[\Override]
    public function resolve($id)
    {
        if (!str_starts_with($id, KeyTemplate\WorkflowTemplate::KEY_PREFIX)) {
            return null;
        }

        $resolvedTemplate = $this->resolveSourceByKey($id);
        if (null === $resolvedTemplate) {
            return null;
        }

        $resolvedId = str_replace('{{ template }}', $resolvedTemplate[0]->getName(), self::TRANSLATION_TEMPLATE);
        $resolvedParameters = $this->resolver->resolveTemplateParameters($resolvedTemplate[1]);

        return $this->translator->trans($resolvedId, $resolvedParameters);
    }

    /**
     * @param string $id
     * @return array|null
     */
    protected function resolveSourceByKey($id)
    {
        $sourceKeyParts = explode('.', $id);

        foreach ($this->templates as $sourceTemplate) {
            $keyTemplates = $sourceTemplate->getKeyTemplates();

            $templateParts = explode('.', $sourceTemplate->getTemplate());

            if (count($sourceKeyParts) !== count($templateParts)) {
                continue;
            }

            $parameters = [];

            foreach ($sourceKeyParts as $key => $part) {
                if (false !== ($attribute = array_search($templateParts[$key], $keyTemplates, true))) {
                    $templateParts[$key] = $part;
                    $parameters[$attribute] = $part;
                }
            }

            if ($sourceKeyParts === $templateParts) {
                return [$sourceTemplate, $parameters];
            }
        }

        return null;
    }
}
