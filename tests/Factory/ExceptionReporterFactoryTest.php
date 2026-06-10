<?php

namespace Meritum\StructuredLogging\Test\Factory;

use Meritum\StructuredLogging\ExceptionReporter;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Factory\ExceptionReporterFactory;
use Meritum\StructuredLogging\Reporting\Reporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ExceptionReporterFactoryTest extends TestCase
{
    private function makeContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ExceptionTranslator::class, $this->createStub(ExceptionTranslator::class)],
                [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
            ]);

        return $container;
    }

    #[Test]
    public function test_returns_exception_reporter(): void
    {
        $factory = new ExceptionReporterFactory();

        $result = $factory($this->makeContainer());

        $this->assertInstanceOf(ExceptionReporter::class, $result);
    }

    #[Test]
    public function test_returns_reporter_instance(): void
    {
        $factory = new ExceptionReporterFactory();

        $result = $factory($this->makeContainer());

        $this->assertInstanceOf(Reporter::class, $result);
    }
}
