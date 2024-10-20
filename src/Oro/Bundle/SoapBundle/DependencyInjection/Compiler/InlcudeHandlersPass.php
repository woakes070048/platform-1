<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InlcudeHandlersPass implements CompilerPassInterface
{
    const TAG                         = 'oro_soap.include_handler';
    const DELEGATE_HANDLER_SERVICE_ID = 'oro_soap.handler.include_delegate';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::DELEGATE_HANDLER_SERVICE_ID)) {
            return;
        }

        $delegateServiceDefinition = $container->getDefinition(self::DELEGATE_HANDLER_SERVICE_ID);
        $tagged                    = $container->findTaggedServiceIds(self::TAG);

        foreach ($tagged as $serviceId => $tag) {
            $alias = isset($tag[0]['alias'])
                ? $tag[0]['alias']
                : $serviceId;

            $delegateServiceDefinition->addMethodCall('registerHandler', [$alias, $serviceId]);
        }
    }
}
