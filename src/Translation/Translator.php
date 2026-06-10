<?php

namespace Meritum\StructuredLogging\Translation;

use Throwable;
use Meritum\StructuredLogging\TranslationHandler;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Exception\DomainException;
use Meritum\StructuredLogging\Exception\UnknownException;

final class Translator implements ExceptionTranslator
{
    /**
     * @var list<TranslationHandler>
     */
    private readonly array $handlers;

    public function __construct(TranslationHandler ...$handlers)
    {
        $sorted = [...$handlers];

        usort($sorted, fn($a, $b) => $b->priority() <=> $a->priority());

        $this->handlers = $sorted;
    }

    public function translate(Throwable $exception): DomainException
    {
        if ($exception instanceof DomainException) {
            return $exception;
        }

        $handler = array_find(
            $this->handlers,
            fn($h, $_) => $h->matches($exception)
        );

        if (null === $handler) {
            return new UnknownException($exception);
        }

        return $handler->handle($exception);
    }
}
