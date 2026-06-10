<?php

namespace Meritum\StructuredLogging\Reporting;

use Throwable;
use Psr\Log\LoggerInterface;
use Meritum\StructuredLogging\ExceptionReporter;
use Meritum\StructuredLogging\ExceptionTranslator;
use Meritum\StructuredLogging\Exception\DomainException;

final class Reporter implements ExceptionReporter
{
    public function __construct(
        private readonly ExceptionTranslator $translator,
        private readonly LoggerInterface $logger
    ) {}

    public function report(Throwable $exception): DomainException
    {
        $e = $this->translator->translate($exception);

        $this->logger->log(
            $e->severity->value,
            $e->getMessage(),
            $e->structuredData
        );

        return $e;
    }
}
