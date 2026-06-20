<?php

namespace Meritum\StructuredLogging\Test\Exception;

use Meritum\StructuredLogging\Exception\UnknownException;
use Meritum\StructuredLogging\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UnknownExceptionTest extends TestCase
{
    #[Test]
    public function test_wraps_original_exception_message(): void
    {
        $original = new RuntimeException('Something broke');

        $exception = new UnknownException($original);

        $this->assertSame('Something broke', $exception->getMessage());
    }

    #[Test]
    public function test_original_exception_is_set_as_previous(): void
    {
        $original = new RuntimeException('cause');

        $exception = new UnknownException($original);

        $this->assertSame($original, $exception->getPrevious());
    }

    #[Test]
    public function test_severity_is_error(): void
    {
        $exception = new UnknownException(new RuntimeException());

        $this->assertSame(Severity::Error, $exception->severity);
    }

    #[Test]
    public function test_is_not_retryable(): void
    {
        $exception = new UnknownException(new RuntimeException());

        $this->assertFalse($exception->retryable);
    }

    #[Test]
    public function test_error_code_is_unknown_0000(): void
    {
        $exception = new UnknownException(new RuntimeException());

        $this->assertSame('UNKNOWN_0000', $exception->getErrorCode());
    }

    #[Test]
    public function test_context_captures_original_exception_details(): void
    {
        $original = new RuntimeException('cause', 99);

        $exception = new UnknownException($original);

        $this->assertSame(RuntimeException::class, $exception->context['original_class']);
        $this->assertSame(99, $exception->context['original_code']);
        $this->assertSame($original->getFile(), $exception->context['original_file']);
        $this->assertSame($original->getLine(), $exception->context['original_line']);
    }

    #[Test]
    public function test_context_line_differs_from_metadata_line(): void
    {
        $original = new RuntimeException('cause');
        $exception = new UnknownException($original);

        $this->assertNotSame($exception->context['original_line'], $exception->metadata['line']);
    }

}
