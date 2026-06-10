<?php

namespace Meritum\StructuredLogging\Test\Reporting;

use Meritum\StructuredLogging\Exception\DomainException;
use Meritum\StructuredLogging\ExceptionReporter;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Reporting\Reporter;
use Meritum\StructuredLogging\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class ReporterTest extends TestCase
{
    private function makeDomainException(string $errorCode = 'TEST_0001', Severity $severity = Severity::Error): DomainException
    {
        return new class($errorCode, $severity) extends DomainException {
            public function __construct(private readonly string $errorCode, Severity $severity)
            {
                parent::__construct('test message', $severity);
            }

            public function getErrorCode(): string
            {
                return $this->errorCode;
            }
        };
    }

    private function makeTranslator(DomainException $returns): ExceptionTranslator
    {
        return new class($returns) implements ExceptionTranslator {
            public function __construct(private readonly DomainException $returns) {}

            public function translate(Throwable $exception): DomainException
            {
                return $this->returns;
            }
        };
    }

    #[Test]
    public function test_implements_exception_reporter(): void
    {
        $reporter = new Reporter(
            $this->makeTranslator($this->makeDomainException()),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertInstanceOf(ExceptionReporter::class, $reporter);
    }

    #[Test]
    public function test_returns_translated_domain_exception(): void
    {
        $domain = $this->makeDomainException();
        $reporter = new Reporter(
            $this->makeTranslator($domain),
            $this->createStub(LoggerInterface::class),
        );

        $result = $reporter->report(new RuntimeException('boom'));

        $this->assertSame($domain, $result);
    }

    #[Test]
    public function test_logs_with_severity_string_value(): void
    {
        $domain = $this->makeDomainException(severity: Severity::Critical);
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('log')
            ->with('critical', $this->anything(), $this->anything());

        $reporter = new Reporter($this->makeTranslator($domain), $logger);
        $reporter->report(new RuntimeException());
    }

    #[Test]
    public function test_logs_with_exception_message(): void
    {
        $domain = $this->makeDomainException();
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('log')
            ->with($this->anything(), 'test message', $this->anything());

        $reporter = new Reporter($this->makeTranslator($domain), $logger);
        $reporter->report(new RuntimeException());
    }

    #[Test]
    public function test_logs_with_structured_data_as_context(): void
    {
        $domain = $this->makeDomainException();
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('log')
            ->with($this->anything(), $this->anything(), $domain->structuredData);

        $reporter = new Reporter($this->makeTranslator($domain), $logger);
        $reporter->report(new RuntimeException());
    }
}
