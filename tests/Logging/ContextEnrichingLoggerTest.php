<?php

namespace Meritum\StructuredLogging\Test\Logging;

use Meritum\StructuredLogging\ContextEnricher;
use Meritum\StructuredLogging\Logging\ContextEnrichingLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ContextEnrichingLoggerTest extends TestCase
{
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
    public function test_implements_logger_interface(): void
    {
        $decorator = new ContextEnrichingLogger($this->createStub(LoggerInterface::class));

        $this->assertInstanceOf(LoggerInterface::class, $decorator);
    }

    #[Test]
    public function test_log_delegates_to_inner_logger(): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method('log')
            ->with('error', 'message', ['key' => 'value']);

        $decorator = new ContextEnrichingLogger($inner);
        $decorator->log('error', 'message', ['key' => 'value']);
    }

    #[Test]
    public function test_enricher_adds_context_to_log(): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method('log')
            ->with('error', 'message', ['original' => true, 'added' => true]);

        $decorator = new ContextEnrichingLogger($inner, $this->makeEnricher(['added' => true]));
        $decorator->log('error', 'message', ['original' => true]);
    }

    #[Test]
    public function test_multiple_enrichers_are_applied_in_order(): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method('log')
            ->with('error', 'message', ['first' => true, 'second' => true]);

        $decorator = new ContextEnrichingLogger(
            $inner,
            $this->makeEnricher(['first' => true]),
            $this->makeEnricher(['second' => true]),
        );

        $decorator->log('error', 'message', []);
    }

    #[Test]
    public function test_no_enrichers_passes_context_unchanged(): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method('log')
            ->with('info', 'message', ['key' => 'value']);

        $decorator = new ContextEnrichingLogger($inner);
        $decorator->log('info', 'message', ['key' => 'value']);
    }

    public static function levelMethodProvider(): array
    {
        return [
            'debug'     => ['debug'],
            'info'      => ['info'],
            'notice'    => ['notice'],
            'warning'   => ['warning'],
            'error'     => ['error'],
            'critical'  => ['critical'],
            'alert'     => ['alert'],
            'emergency' => ['emergency'],
        ];
    }

    #[Test]
    #[DataProvider('levelMethodProvider')]
    public function test_level_method_delegates_to_inner_logger(string $level): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method($level)
            ->with('message', ['key' => 'value']);

        $decorator = new ContextEnrichingLogger($inner);
        $decorator->$level('message', ['key' => 'value']);
    }

    #[Test]
    #[DataProvider('levelMethodProvider')]
    public function test_level_method_enriches_context(string $level): void
    {
        $inner = $this->createMock(LoggerInterface::class);
        $inner->expects($this->once())
            ->method($level)
            ->with('message', ['original' => true, 'added' => true]);

        $decorator = new ContextEnrichingLogger($inner, $this->makeEnricher(['added' => true]));
        $decorator->$level('message', ['original' => true]);
    }
}
