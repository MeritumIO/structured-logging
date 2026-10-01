<?php

namespace Meritum\StructuredLogging\Factory;

use Psr\Container\ContainerInterface;
use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\StructuredLogging\TranslationHandler;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Translation\Translator;
use Meritum\StructuredLogging\StructuredLoggingOption;

final class ExceptionTranslatorFactory
{
    public function __invoke(ContainerInterface $container): ExceptionTranslator
    {
        $tags = $container->get(TagRegistryInterface::class);

        return new Translator(...$this->getTranslationHandlers($tags));
    }

    /**
     * @return TranslationHandler[]
     */
    private function getTranslationHandlers(TagRegistryInterface $tags): array
    {
        /** @var TranslationHandler[] $handlers */
        $handlers = $tags->getTagged(StructuredLoggingOption::TranslatorTag->value);

        return $handlers;
    }
}
