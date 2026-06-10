<?php

namespace Meritum\StructuredLogging\Test\Exception;

use DateTimeInterface;
use Meritum\StructuredLogging\Exception\DomainException;
use Meritum\StructuredLogging\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DomainExceptionTest extends TestCase
{
    private function makeException(
        string $message = 'Something went wrong',
        Severity $severity = Severity::Error,
        array $context = [],
        bool $retryable = false,
        int $code = 0,
        ?\Throwable $previous = null,
    ): DomainException {
        return new class($message, $severity, $context, $retryable, $code, $previous) extends DomainException {
            public function getErrorCode(): string
            {
                return 'TEST_0001';
            }
        };
    }

    #[Test]
    public function test_constructor_sets_properties(): void
    {
        $previous = new RuntimeException('cause');
        $exception = $this->makeException(
            message: 'Oops',
            severity: Severity::Warning,
            context: ['key' => 'value'],
            retryable: true,
            code: 42,
            previous: $previous,
        );

        $this->assertSame('Oops', $exception->getMessage());
        $this->assertSame(Severity::Warning, $exception->severity);
        $this->assertSame(['key' => 'value'], $exception->context);
        $this->assertTrue($exception->retryable);
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function test_constructor_defaults(): void
    {
        $exception = $this->makeException();

        $this->assertSame([], $exception->context);
        $this->assertFalse($exception->retryable);
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function test_occurred_at_is_set_on_construction(): void
    {
        $before = new \DateTimeImmutable();
        $exception = $this->makeException();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $exception->occurredAt);
        $this->assertLessThanOrEqual($after, $exception->occurredAt);
    }

    #[Test]
    public function test_structured_data_shape(): void
    {
        $exception = $this->makeException(
            message: 'Test message',
            severity: Severity::Critical,
            context: ['foo' => 'bar'],
            retryable: true,
        );

        $data = $exception->structuredData;

        $this->assertSame('TEST_0001', $data['error_code']);
        $this->assertSame('Test message', $data['message']);
        $this->assertSame('critical', $data['severity']);
        $this->assertTrue($data['retryable']);
        $this->assertSame(['foo' => 'bar'], $data['detail']);
        $this->assertArrayHasKey('occurred_at', $data);
        $this->assertArrayHasKey('metadata', $data);
    }

    #[Test]
    public function test_structured_data_occurred_at_is_rfc3339(): void
    {
        $exception = $this->makeException();

        $occurredAt = $exception->structuredData['occurred_at'];

        $this->assertNotFalse(\DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $occurredAt));
    }

    #[Test]
    public function test_structured_data_severity_is_string_value(): void
    {
        foreach (Severity::cases() as $severity) {
            $exception = $this->makeException(severity: $severity);

            $this->assertSame($severity->value, $exception->structuredData['severity']);
        }
    }

    #[Test]
    public function test_metadata_contains_expected_keys(): void
    {
        $exception = $this->makeException();

        $metadata = $exception->metadata;

        $this->assertArrayHasKey('file', $metadata);
        $this->assertArrayHasKey('line', $metadata);
        $this->assertArrayHasKey('class', $metadata);
    }

    #[Test]
    public function test_metadata_class_is_concrete_subclass(): void
    {
        $exception = $this->makeException();

        $this->assertStringContainsString('DomainExceptionTest', $exception->metadata['class']);
    }

    #[Test]
    public function test_metadata_is_cached(): void
    {
        $exception = $this->makeException();

        $this->assertSame($exception->metadata, $exception->metadata);
    }

    #[Test]
    public function test_generate_error_code_suffix_pads_to_four_digits(): void
    {
        $exception = new class extends DomainException {
            public function __construct()
            {
                parent::__construct('msg', Severity::Error);
            }

            public function getErrorCode(): string
            {
                return 'X_' . $this->generateErrorCodeSuffix(1);
            }

            public function suffix(int $code): string
            {
                return $this->generateErrorCodeSuffix($code);
            }
        };

        $this->assertSame('0001', $exception->suffix(1));
        $this->assertSame('0042', $exception->suffix(42));
        $this->assertSame('1234', $exception->suffix(1234));
        $this->assertSame('0000', $exception->suffix(0));
    }
}
