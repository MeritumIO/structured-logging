<?php

namespace Meritum\StructuredLogging\Test\Factory;

use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\StructuredLogging\ContextEnricher;
use Meritum\StructuredLogging\Factory\LoggerDecoratorFactory;
use Meritum\StructuredLogging\Logging\ContextEnrichingLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerDecoratorFactoryTest extends TestCase
{
    private function makeContainer(array $enrichers = []): ContainerInterface
    {
        $tags = $this->createMock(TagRegistryInterface::class);
        $tags->method('getTagged')
            ->with('log.context.enrichers')
            ->willReturn($enrichers);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with(TagRegistryInterface::class)
            ->willReturn($tags);

        return $container;
    }

    private function makeEnricher(array $additions): ContextEnricher
    {
        return new class($additions) implements ContextEnricher {
            public function __construct(private readonly array $additions) {}

            public function enrich(array $context): array
            {
                return array_merge($context, $this->additions);
            }
        };
    }

    #[Test]
    public function test_returns_logger_interface(): void
    {
        $factory = new LoggerDecoratorFactory();

        $result = $factory($this->createStub(LoggerInterface::class), $this->makeContainer());

        $this->assertInstanceOf(LoggerInterface::class, $result);
    }

    #[Test]
    public function test_returns_context_enriching_logger(): void
    {
        $factory = new LoggerDecoratorFactory();

        $result = $factory($this->createStub(LoggerInterface::class), $this->makeContainer());

        $this->assertInstanceOf(ContextEnrichingLogger::class, $result);
    }


}
