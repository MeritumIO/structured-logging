<?php

namespace Meritum\StructuredLogging\Test\Translation;

use Meritum\StructuredLogging\Exception\DomainException;
use Meritum\StructuredLogging\Exception\UnknownException;
use Meritum\StructuredLogging\Severity;
use Meritum\StructuredLogging\TranslationHandler;
use Meritum\StructuredLogging\Translation\Translator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class TranslatorTest extends TestCase
{
    private function makeHandler(bool $matches, DomainException $result, int $priority = 0): TranslationHandler
    {
        return new class($matches, $result, $priority) implements TranslationHandler {
            public function __construct(
                private readonly bool $matches,
                private readonly DomainException $result,
                private readonly int $priority,
            ) {}

            public function matches(Throwable $exception): bool
            {
                return $this->matches;
            }

            public function handle(Throwable $exception): DomainException
            {
                return $this->result;
            }

            public function priority(): int
            {
                return $this->priority;
            }
        };
    }

    private function makeDomainException(string $errorCode = 'TEST_0001'): DomainException
    {
        return new class($errorCode) extends DomainException {
            public function __construct(private readonly string $errorCode)
            {
                parent::__construct('test', Severity::Error);
            }

            public function getErrorCode(): string
            {
                return $this->errorCode;
            }
        };
    }

    #[Test]
    public function test_domain_exception_is_returned_as_is(): void
    {
        $translator = new Translator();
        $exception = $this->makeDomainException();

        $result = $translator->translate($exception);

        $this->assertSame($exception, $result);
    }

    #[Test]
    public function test_returns_unknown_exception_when_no_handlers_registered(): void
    {
        $translator = new Translator();

        $result = $translator->translate(new RuntimeException('boom'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    #[Test]
    public function test_returns_unknown_exception_when_no_handler_matches(): void
    {
        $translator = new Translator(
            $this->makeHandler(false, $this->makeDomainException()),
        );

        $result = $translator->translate(new RuntimeException('boom'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    #[Test]
    public function test_delegates_to_matching_handler(): void
    {
        $expected = $this->makeDomainException();
        $translator = new Translator(
            $this->makeHandler(true, $expected),
        );

        $result = $translator->translate(new RuntimeException('boom'));

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function test_higher_priority_handler_wins(): void
    {
        $low = $this->makeDomainException('LOW_0001');
        $high = $this->makeDomainException('HIGH_0001');

        $translator = new Translator(
            $this->makeHandler(true, $low, priority: 0),
            $this->makeHandler(true, $high, priority: 10),
        );

        $result = $translator->translate(new RuntimeException('boom'));

        $this->assertSame($high, $result);
    }

    #[Test]
    public function test_first_matching_handler_used_when_priorities_differ(): void
    {
        $matched = $this->makeDomainException('MATCHED_0001');

        $translator = new Translator(
            $this->makeHandler(false, $this->makeDomainException(), priority: 5),
            $this->makeHandler(true, $matched, priority: 0),
        );

        $result = $translator->translate(new RuntimeException('boom'));

        $this->assertSame($matched, $result);
    }

    #[Test]
    public function test_unknown_exception_wraps_original(): void
    {
        $translator = new Translator();
        $original = new RuntimeException('original message');

        $result = $translator->translate($original);

        $this->assertInstanceOf(UnknownException::class, $result);
        $this->assertSame($original, $result->getPrevious());
    }
}
