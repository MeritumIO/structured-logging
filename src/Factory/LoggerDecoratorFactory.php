<?php

namespace Meritum\StructuredLogging\Factory;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\StructuredLogging\ContextEnricher;
use Meritum\StructuredLogging\Logging\ContextEnrichingLogger;

final class LoggerDecoratorFactory
{
    public function __invoke(mixed $inner, ContainerInterface $container): LoggerInterface
    {
        assert($inner instanceof LoggerInterface);

        $tags = $container->get(TagRegistryInterface::class);

        return new ContextEnrichingLogger($inner, ...$this->getContextEnrichers($tags));
    }

    /**
     * @return ContextEnricher[]
     */
    private function getContextEnrichers(TagRegistryInterface $tags): array
    {
        /** @var ContextEnricher[] $enrichers */
        $enrichers = $tags->getTagged('log.context.enrichers');

        return $enrichers;
    }
}
