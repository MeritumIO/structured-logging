<?php

namespace Meritum\StructuredLogging;

use Psr\Log\LoggerInterface;
use Georgeff\Kernel\KernelInterface;
use Psr\Container\ContainerInterface;
use Georgeff\Kernel\Module\ModuleInterface;
use Meritum\StructuredLogging\Context\CorrelationIdEnricher;
use Meritum\StructuredLogging\Factory\LoggerDecoratorFactory;
use Meritum\StructuredLogging\Factory\ExceptionReporterFactory;
use Meritum\StructuredLogging\Factory\ExceptionTranslatorFactory;

final class StructuredLoggingModule implements ModuleInterface
{
    public function register(KernelInterface $kernel): void
    {
        $kernel->define(CorrelationId::class, fn() => new CorrelationId())->share();

        $kernel->define(
            CorrelationIdEnricher::class,
            fn(ContainerInterface $c) => new CorrelationIdEnricher($c->get(CorrelationId::class))
        )->tag('log.context.enrichers');

        $kernel->define(ExceptionTranslator::class, new ExceptionTranslatorFactory());

        $kernel->define(ExceptionReporter::class, new ExceptionReporterFactory());

        $kernel->decorate(LoggerInterface::class, new LoggerDecoratorFactory());
    }
}
