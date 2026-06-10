<?php

namespace Meritum\StructuredLogging\Test;

use Georgeff\Kernel\Environment;
use Georgeff\Kernel\Kernel;
use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\StructuredLogging\Context\CorrelationIdEnricher;
use Meritum\StructuredLogging\CorrelationId;
use Meritum\StructuredLogging\ExceptionReporter;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Logging\ContextEnrichingLogger;
use Meritum\StructuredLogging\StructuredLoggingModule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class StructuredLoggingModuleTest extends TestCase
{
    private function makeKernel(): Kernel
    {
        $kernel = new Kernel(Environment::Testing);
        $kernel->define(LoggerInterface::class, fn() => new NullLogger());
        $kernel->addModule(new StructuredLoggingModule());
        $kernel->boot();

        return $kernel;
    }

    #[Test]
    public function test_correlation_id_resolves(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertInstanceOf(CorrelationId::class, $container->get(CorrelationId::class));
    }

    #[Test]
    public function test_correlation_id_is_shared(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertSame(
            $container->get(CorrelationId::class),
            $container->get(CorrelationId::class)
        );
    }

    #[Test]
    public function test_correlation_id_enricher_resolves(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertInstanceOf(CorrelationIdEnricher::class, $container->get(CorrelationIdEnricher::class));
    }

    #[Test]
    public function test_correlation_id_enricher_is_tagged(): void
    {
        $container = $this->makeKernel()->getContainer();
        $tags = $container->get(TagRegistryInterface::class);

        $enrichers = $tags->getTagged('log.context.enrichers');

        $this->assertCount(1, $enrichers);
        $this->assertInstanceOf(CorrelationIdEnricher::class, $enrichers[0]);
    }

    #[Test]
    public function test_exception_translator_resolves(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertInstanceOf(ExceptionTranslator::class, $container->get(ExceptionTranslator::class));
    }

    #[Test]
    public function test_exception_reporter_resolves(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertInstanceOf(ExceptionReporter::class, $container->get(ExceptionReporter::class));
    }

    #[Test]
    public function test_logger_is_decorated_with_context_enriching_logger(): void
    {
        $container = $this->makeKernel()->getContainer();

        $this->assertInstanceOf(ContextEnrichingLogger::class, $container->get(LoggerInterface::class));
    }
}
